<?php
class SimpleDB {
    private array $tables = [];
    private string $dataDir;
    private int $lastId = 0;
    private bool $inTransaction = false;

    public function __construct() {
        $this->dataDir = __DIR__ . '/../data';
        if (!is_dir($this->dataDir)) mkdir($this->dataDir, 0755, true);
        $this->loadAll();
    }

    private function loadAll(): void {
        foreach (glob($this->dataDir . '/*.json') as $f) {
            $name = basename($f, '.json');
            $content = file_get_contents($f);
            $this->tables[$name] = $content ? json_decode($content, true) : [];
        }
    }

    private function save(string $table): void {
        file_put_contents($this->dataDir . '/' . $table . '.json', json_encode($this->tables[$table] ?? [], JSON_PRETTY_PRINT));
    }

    public function prepare(string $sql): SimpleStatement {
        return new SimpleStatement($this, $sql);
    }

    public function exec(string $sql): int {
        $this->parseAndExecute($sql, []);
        return 0;
    }

    public function query(string $sql): SimpleStatement {
        $stmt = new SimpleStatement($this, $sql);
        $stmt->execute([]);
        return $stmt;
    }

    public function lastInsertId(): string {
        return (string)$this->lastId;
    }

    public function beginTransaction(): bool {
        $this->inTransaction = true;
        return true;
    }

    public function commit(): bool {
        $this->inTransaction = true;
        return true;
    }

    public function rollBack(): bool {
        return true;
    }

    public function parseAndExecute(string $sql, array $params): array {
        $sql = trim(preg_replace('/\s+/', ' ', $sql));
        $upper = strtoupper($sql);

        if (str_starts_with($upper, 'SELECT')) {
            return $this->handleSelect($sql, $params);
        }
        if (str_starts_with($upper, 'INSERT')) {
            return $this->handleInsert($sql, $params);
        }
        if (str_starts_with($upper, 'UPDATE')) {
            $this->handleUpdate($sql, $params);
            return [];
        }
        if (str_starts_with($upper, 'DELETE')) {
            $this->handleDelete($sql, $params);
            return [];
        }
        return [];
    }

    private function extractTable(string $sql): ?string {
        if (preg_match('/\bFROM\s+(\w+)\b/i', $sql, $m)) return $m[1];
        if (preg_match('/\bINTO\s+(\w+)\b/i', $sql, $m)) return $m[1];
        if (preg_match('/\bUPDATE\s+(\w+)\b/i', $sql, $m)) return $m[1];
        return null;
    }

    private function resolveJoin(string $sql): ?array {
        if (preg_match('/SELECT\s+(.*?)\s+FROM\s+(\w+)\s+(?:AS\s+)?(\w+)?\s+JOIN\s+(\w+)\s+(?:AS\s+)?(\w+)?\s+ON\s+(.*?)(?:\s+WHERE\s+(.*))?$/i', $sql, $m)) {
            return [
                'select' => $m[1],
                'table1' => $m[2],
                'alias1' => $m[3] ?: $m[2],
                'table2' => $m[4],
                'alias2' => $m[5] ?: $m[4],
                'on' => $m[6],
                'where' => $m[7] ?? ''
            ];
        }
        return null;
    }

    private function handleSelect(string $sql, array $params): array {
        $join = $this->resolveJoin($sql);
        if ($join) {
            return $this->handleJoinSelect($join, $params);
        }

        $table = $this->extractTable($sql);
        if (!$table || !isset($this->tables[$table])) return [];

        $rows = $this->tables[$table];

        // Extract WHERE conditions
        if (preg_match('/\bWHERE\s+(.*?)(?:\s+ORDER\s+BY|\s+LIMIT|\s*$)/is', $sql, $m)) {
            $whereClause = $m[1];
            $conditions = $this->parseWhere($whereClause);
            $rows = $this->filter($rows, $conditions, $params);
        }

        // ORDER BY
        if (preg_match('/\bORDER\s+BY\s+(\w+)\b/i', $sql, $m)) {
            $col = $m[1];
            $desc = stripos($sql, 'DESC') !== false;
            usort($rows, function($a, $b) use ($col, $desc) {
                $cmp = ($a[$col] ?? '') <=> ($b[$col] ?? '');
                return $desc ? -$cmp : $cmp;
            });
        }

        // LIMIT
        if (preg_match('/\bLIMIT\s+(\d+)\b/i', $sql, $m)) {
            $rows = array_slice($rows, 0, (int)$m[1]);
        }

        // Extract selected columns
        if (preg_match('/SELECT\s+(.*?)\s+FROM/i', $sql, $m)) {
            $selectPart = trim($m[1]);
            if ($selectPart === '*') return $rows;

            $cols = explode(',', $selectPart);
            $cols = array_map(fn($c) => trim(preg_replace('/.*\.(\w+)/', '$1', $c)), $cols);
            return array_map(fn($r) => array_intersect_key($r, array_flip($cols)), $rows);
        }

        return $rows;
    }

    private function handleJoinSelect(array $join, array $params): array {
        $t1 = $this->tables[$join['table1']] ?? [];
        $t2 = $this->tables[$join['table2']] ?? [];
        $a1 = $join['alias1'];
        $a2 = $join['alias2'];

        // Parse ON clause: a.col = b.col
        preg_match('/(\w+)\.(\w+)\s*=\s*(\w+)\.(\w+)/', $join['on'], $onM);
        $leftTable = $onM[1] === $a1 ? $join['table1'] : $join['table2'];
        $leftCol = $onM[2];
        $rightTable = $onM[3] === $a2 ? $join['table2'] : $join['table1'];
        $rightCol = $onM[4];

        $results = [];
        foreach ($t1 as $r1) {
            foreach ($t2 as $r2) {
                $lk = $leftTable === $join['table1'] ? $r1[$leftCol] : $r2[$leftCol];
                $rk = $rightTable === $join['table2'] ? $r2[$rightCol] : $r1[$rightCol];
                if ($lk == $rk) {
                    $merged = array_merge($r1, $r2);
                    $results[] = $merged;
                }
            }
        }

        // Apply WHERE
        if ($join['where']) {
            $conditions = $this->parseWhere($join['where']);
            $results = $this->filter($results, $conditions, $params);
        }

        return $results;
    }

    private function parseWhere(string $clause): array {
        $conditions = [];
        $parts = preg_split('/\bAND\b/i', $clause);
        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/(\w+\.\w+|\w+)\s*(=|!=|<>|>=|<=|>|<|LIKE|IS\s+NOT\s+NULL|IS\s+NULL)\s*(.*)/i', $part, $m)) {
                $col = str_contains($m[1], '.') ? substr($m[1], strpos($m[1], '.') + 1) : $m[1];
                $op = strtoupper(trim($m[2]));
                $val = trim($m[3]);
                $conditions[] = ['col' => $col, 'op' => $op, 'val' => $val];
            }
        }
        return $conditions;
    }

    private function filter(array $rows, array $conditions, array $params): array {
        $paramIdx = 0;
        foreach ($conditions as $cond) {
            $val = $cond['val'];
            if ($val === '?') {
                $val = $params[$paramIdx] ?? null;
                $paramIdx++;
            } elseif (str_starts_with($val, ':')) {
                $val = $params[$val] ?? $params[$paramIdx] ?? null;
                $paramIdx++;
            } elseif (strtoupper($val) === 'NULL') {
                $val = null;
            } else {
                $val = trim($val, "'\"");
            }
            // Handle IS NULL / IS NOT NULL
            if (in_array($cond['op'], ['IS NULL', 'IS NOT NULL'])) {
                $isNull = $cond['op'] === 'IS NULL';
                $rows = array_values(array_filter($rows, fn($r) => $isNull ? !isset($r[$cond['col']]) || $r[$cond['col']] === null : isset($r[$cond['col']]) && $r[$cond['col']] !== null));
            } else {
                $rows = array_values(array_filter($rows, fn($r) => isset($r[$cond['col']]) && $r[$cond['col']] == $val));
            }
        }
        return $rows;
    }

    private function handleInsert(string $sql, array $params): array {
        $table = $this->extractTable($sql);
        if (!$table) return [];

        // Parse columns and values
        preg_match('/INSERT\s+INTO\s+\w+\s*\((.*?)\)\s*VALUES\s*\((.*?)\)/is', $sql, $m);
        $cols = array_map('trim', explode(',', $m[1]));
        $vals = array_map('trim', explode(',', $m[2]));

        $row = [];
        $paramIdx = 0;
        foreach ($cols as $i => $col) {
            $v = $vals[$i] ?? '?';
            if ($v === '?' || $v === '?') {
                $row[$col] = $params[$paramIdx] ?? null;
                $paramIdx++;
            } else {
                $row[$col] = trim($v, "'\"");
            }
        }

        // Auto-generate ID
        if (!isset($row['id']) || !$row['id']) {
            $existingIds = array_map(fn($r) => (int)($r['id'] ?? 0), $this->tables[$table] ?? []);
            $row['id'] = $existingIds ? max($existingIds) + 1 : 1;
            // Try to use id from params if it's an INSERT with explicit id
            if (in_array('id', $cols)) {
                $idIdx = array_search('id', $cols);
                if ($idIdx !== false && isset($params[$idIdx])) {
                    $row['id'] = (int)$params[$idIdx];
                }
            }
        }

        $this->lastId = (int)$row['id'];
        $this->tables[$table][] = $row;
        $this->save($table);

        return [$row];
    }

    private function handleUpdate(string $sql, array $params): void {
        $table = $this->extractTable($sql);
        if (!$table || !isset($this->tables[$table])) return;

        preg_match('/UPDATE\s+\w+\s+SET\s+(.*?)(?:\s+WHERE\s+(.*))?$/is', $sql, $m);
        $setClause = $m[1];
        $whereClause = $m[2] ?? '';

        // Parse SET assignments
        $sets = [];
        $setParts = explode(',', $setClause);
        foreach ($setParts as $sp) {
            if (preg_match('/(\w+)\s*=\s*(.*)/', trim($sp), $sm)) {
                $sets[] = ['col' => $sm[1], 'val' => trim($sm[2])];
            }
        }

        $rows = &$this->tables[$table];
        $paramIdx = 0;

        // Assign param positions: first params are SET values, then WHERE values
        $setCount = count($sets);
        $whereParams = [];
        if ($whereClause) {
            $conditions = $this->parseWhere($whereClause);
            $rows = $this->filter($rows, $conditions, $params);
            // Track used params
        }

        // Re-map params: for each row, apply SET
        $allRows = &$this->tables[$table];
        $filtered = [];
        if ($whereClause) {
            $conditions = $this->parseWhere($whereClause);
            foreach ($allRows as $idx => $r) {
                $match = true;
                $pIdx = $setCount; // WHERE params start after SET params
                foreach ($conditions as $cond) {
                    $val = $cond['val'];
                    if ($val === '?') {
                        $val = $params[$pIdx] ?? null;
                        $pIdx++;
                    }
                    if ($cond['op'] === '>=') {
                        if (!(($r[$cond['col']] ?? 0) >= $val)) { $match = false; break; }
                    } elseif ($cond['op'] === '<=') {
                        if (!(($r[$cond['col']] ?? 0) <= $val)) { $match = false; break; }
                    } elseif ($cond['op'] === '>') {
                        if (!(($r[$cond['col']] ?? 0) > $val)) { $match = false; break; }
                    } elseif ($cond['op'] === '<') {
                        if (!(($r[$cond['col']] ?? 0) < $val)) { $match = false; break; }
                    } elseif ($cond['op'] === '!=' || $cond['op'] === '<>') {
                        if (($r[$cond['col']] ?? null) == $val) { $match = false; break; }
                    } else {
                        if (($r[$cond['col']] ?? null) != $val) { $match = false; break; }
                    }
                }
                if ($match) $filtered[] = $idx;
            }
        } else {
            $filtered = array_keys($allRows);
        }

        // Apply SET
        foreach ($filtered as $idx) {
            $setIdx = 0;
            foreach ($sets as $set) {
                $valExpr = $set['val'];
                if ($valExpr === '?') {
                    $val = $params[$setIdx] ?? null;
                    $setIdx++;
                } elseif (str_contains($valExpr, 'stock - ?')) {
                    $qty = $params[$setIdx] ?? 0;
                    $val = max(0, ($allRows[$idx]['stock'] ?? 0) - $qty);
                    $setIdx++;
                    // For stock >= ? condition also consume a param
                } elseif (str_contains($valExpr, 'stock + ?')) {
                    $qty = $params[$setIdx] ?? 0;
                    $val = ($allRows[$idx]['stock'] ?? 0) + $qty;
                    $setIdx++;
                } else {
                    $val = trim($valExpr, "'\"");
                }
                // Handle expressions like "cantidad + ?"
                if (is_string($val) && str_contains($val, 'cantidad + ?')) {
                    $val = ($allRows[$idx]['cantidad'] ?? 0) + ($params[$setIdx - 1] ?? 0);
                } elseif (is_string($val) && str_contains($val, 'stock - ?')) {
                    $val = max(0, ($allRows[$idx]['stock'] ?? 0) - ($params[$setIdx - 1] ?? 0));
                }
                $allRows[$idx][$set['col']] = $val;
            }
        }

        $this->save($table);
    }

    private function handleDelete(string $sql, array $params): void {
        $table = $this->extractTable($sql);
        if (!$table || !isset($this->tables[$table])) return;

        preg_match('/DELETE\s+FROM\s+\w+(?:\s+WHERE\s+(.*))?$/is', $sql, $m);
        $whereClause = $m[1] ?? '';

        if (!$whereClause) {
            $this->tables[$table] = [];
        } else {
            $conditions = $this->parseWhere($whereClause);
            $keep = [];
            $paramIdx = 0;
            foreach ($this->tables[$table] as $r) {
                $match = true;
                $pIdx = 0;
                foreach ($conditions as $cond) {
                    $val = $cond['val'];
                    if ($val === '?') {
                        $val = $params[$pIdx] ?? null;
                        $pIdx++;
                    }
                    if (($r[$cond['col']] ?? null) != $val) { $match = false; break; }
                }
                if (!$match) $keep[] = $r;
            }
            $this->tables[$table] = $keep;
        }
        $this->save($table);
    }
}

class SimpleStatement {
    private SimpleDB $db;
    private string $sql;
    private ?array $results = null;

    public function __construct(SimpleDB $db, string $sql) {
        $this->db = $db;
        $this->sql = $sql;
    }

    public function execute(array $params = []): bool {
        $upper = strtoupper(trim($this->sql));
        if (str_starts_with($upper, 'SELECT') || str_starts_with($upper, 'WITH')) {
            $this->results = $this->db->parseAndExecute($this->sql, $params);
        } elseif (str_starts_with($upper, 'INSERT')) {
            $this->results = $this->db->parseAndExecute($this->sql, $params);
        } else {
            $this->db->parseAndExecute($this->sql, $params);
        }
        return true;
    }

    public function fetch(): ?array {
        if ($this->results === null) return null;
        $row = current($this->results);
        if ($row === false) return null;
        next($this->results);
        return $row;
    }

    public function fetchAll(): array {
        $r = $this->results ?? [];
        $this->results = [];
        return $r;
    }

    public function fetchColumn(int $column = 0): mixed {
        $row = $this->fetch();
        if (!$row) return null;
        $values = array_values($row);
        return $values[$column] ?? null;
    }
}

try {
    $pdo = new SimpleDB();
} catch (Exception $e) {
    $pdo = null;
}
