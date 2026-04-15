<?php

namespace Ivy\Trait;

trait HasDirtyChecking
{
    public function isDirty(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->columns, true)) {
                continue;
            }

            if (!property_exists($this, $key)) {
                continue;
            }

            if ((string) $this->$key !== (string) $value) {
                return true;
            }
        }

        return false;
    }
}