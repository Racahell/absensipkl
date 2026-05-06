<?php

namespace App\Models\Builders;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;

class UserBuilder extends Builder
{
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($this->isRoleColumn($column)) {
            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }
            $column = $this->qualifyRoleIdColumn();
            $value = $this->mapRoleValue($value);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if ($this->isRoleColumn($column)) {
            $column = $this->qualifyRoleIdColumn();
            $values = collect($values)->map(fn ($value) => $this->mapRoleValue($value))->all();
        }

        return parent::whereIn($column, $values, $boolean, $not);
    }

    private function isRoleColumn(mixed $column): bool
    {
        if (! is_string($column)) {
            return false;
        }

        return in_array($column, ['role', 'users.role'], true);
    }

    private function qualifyRoleIdColumn(): string
    {
        return $this->getModel()->getTable().'.role_id';
    }

    private function mapRoleValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $roleKey = trim($value);
        if ($roleKey === '') {
            return null;
        }

        $roleId = Role::query()->where('key', $roleKey)->value('id');
        return $roleId ?? 0;
    }
}
