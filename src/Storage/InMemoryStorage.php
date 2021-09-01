<?php

namespace BoxUk\Dictator\Storage;

class InMemoryStorage implements Storage, \ArrayAccess
{
    private $data = [];

    public function save(array $data) {
        $this->data[] = $data;
    }

    public function getAll(): array
    {
        return $this->data;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}
