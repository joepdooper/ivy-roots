<?php

namespace Ivy;

class Setting extends Model
{
    use Stash;

    protected string $table = 'setting';
    protected string $path = 'admin/setting';
    protected array $columns = [
        'name',
        'bool',
        'value',
        'info'
    ];

    protected string $name;
    protected int $bool;
    protected string $value;
    protected string $info;


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getBool(): int
    {
        return $this->bool;
    }

    /**
     * @param int $bool
     */
    public function setBool(int $bool): void
    {
        $this->bool = $bool;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getInfo(): string
    {
        return $this->info;
    }

    /**
     * @param string $info
     */
    public function setInfo(string $info): void
    {
        $this->info = $info;
    }
}
