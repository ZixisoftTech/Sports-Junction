<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use PDO;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function validate(array $rules): bool
    {
        $this->errors = [];
        $db = Database::getConnection();

        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $rule = trim($rule);

                if ($rule === 'required') {
                    if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                        break; // Stop further checks on empty required field
                    }
                }

                if ($value === null || (is_string($value) && trim($value) === '')) {
                    continue; // Skip further checks if optional and empty
                }

                if ($rule === 'email') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($field, 'Please enter a valid email address.');
                    }
                }

                if ($rule === 'min') {
                    $min = (int)($params[0] ?? 0);
                    if (is_string($value) && mb_strlen($value) < $min) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.");
                    }
                }

                if ($rule === 'max') {
                    $max = (int)($params[0] ?? 255);
                    if (is_string($value) && mb_strlen($value) > $max) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$max} characters.");
                    }
                }

                if ($rule === 'in') {
                    if (!in_array($value, $params, true)) {
                        $this->addError($field, 'The selected ' . str_replace('_', ' ', $field) . ' is invalid.');
                    }
                }

                if ($rule === 'alpha_dash') {
                    if (!preg_match('/^[a-zA-Z0-9_-]+$/', (string)$value)) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' may only contain letters, numbers, dashes and underscores.');
                    }
                }

                if ($rule === 'integer') {
                    if (filter_var($value, FILTER_VALIDATE_INT) === false && $value !== '0' && $value !== 0) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a valid integer.');
                    }
                }

                if ($rule === 'numeric') {
                    if (!is_numeric($value)) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a valid number.');
                    }
                }

                if ($rule === 'unique') {
                    // Syntax: unique:table,column,ignoreId,idColumn
                    $table = $params[0] ?? '';
                    $column = $params[1] ?? $field;
                    $ignoreId = $params[2] ?? null;
                    $idCol = $params[3] ?? 'id';

                    $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
                    $queryParams = [':val' => trim((string)$value)];

                    if (!empty($ignoreId) && $ignoreId !== 'NULL') {
                        $sql .= " AND `{$idCol}` != :ignoreId";
                        $queryParams[':ignoreId'] = $ignoreId;
                    }

                    $stmt = $db->prepare($sql);
                    $stmt->execute($queryParams);
                    if ((int)$stmt->fetchColumn() > 0) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' already exists. Please choose a unique value.');
                    }
                }

                if ($rule === 'exists') {
                    // Syntax: exists:table,column
                    $table = $params[0] ?? '';
                    $column = $params[1] ?? 'id';

                    $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':val' => $value]);
                    if ((int)$stmt->fetchColumn() === 0) {
                        $this->addError($field, 'Selected ' . str_replace('_', ' ', $field) . ' does not exist.');
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }
}
