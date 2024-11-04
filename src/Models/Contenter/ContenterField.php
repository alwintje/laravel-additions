<?php

namespace Kroesen\LaravelAdditions\Models\Contenter;

use Closure;
use Exception;

class ContenterField
{
    public const APPLICABLE_TEXT = 'text';
    public const APPLICABLE_POSITIVE_NUMBER = 'positive number';
    public const APPLICABLE_NEGATIVE_NUMBER = 'negative number';
    public const APPLICABLE_NOT_EMPTY = 'not empty';
    public bool|Closure $applicable;
    public function __construct(
        protected string    $name,
        protected mixed     $default,
        Closure|bool|string $applicable,
        public null|Closure $action,

    )
    {
        if($this->name === 'sorting'){
            throw new Exception('Field "sorting" is reserved and cannot be used');
        }
        if($applicable === self::APPLICABLE_TEXT){
            $this->applicable = fn($builder, $data) => $data !== null && $data !== '';
        }elseif($applicable === self::APPLICABLE_POSITIVE_NUMBER){
            $this->applicable = fn($builder, $data) => $data !== null && ((int) $data) > 0;
            if($this->action === null){
                $this->action = fn($builder, &$data) => $data = (int) $data;
            }
        }elseif($applicable === self::APPLICABLE_NEGATIVE_NUMBER){
            $this->applicable = fn($builder, $data) => $data !== null && ((int) $data) < 0;
            if($this->action === null){
                $this->action = fn($builder, &$data) => $data = (int) $data;
            }
        }elseif($applicable === self::APPLICABLE_NOT_EMPTY){
            $this->applicable = fn($builder, $data) => !empty($data);
        }else{
            $this->applicable = $applicable;
        }
    }

    public static function make(
        string $name,
        mixed $default,
        Closure|bool|string $applicable,
        Closure $action
    ): static {
        return new static($name, $default, $applicable, $action);
    }
    public static function search(string $name, Closure $action): static
    {
        return new static(
            $name,
            '',
            self::APPLICABLE_TEXT,
            fn ($query, $search) => $action($query, $search === null ? null : trim($search)),
        );
    }

    public static function perPage(int $default = 15): static
    {
        return new static('perPage', $default, true, null);
    }
    public static function page(int $default = 1): static
    {
        return new static('page', $default, true, null);
    }

    public static function buttonFilter(string $name, array $options, ?Closure $action = null): static
    {

        $default = array_fill_keys($options, true);

        if($action === null){
            $action = function($builder, $mustExists, $mustNotExists) use ($name) {
                $builder
                    ->whereIn($name, $mustExists)
                    ->whereNotIn($name, $mustNotExists)
                ;
            };
        }

        $action = function($builder, $data) use ($action) {
            $mustNotExists = [];
            $mustExists = [];
            foreach ($data as $key => $bool){
                if($bool === 'true'){
                    $mustExists[] = $key;
                }else{
                    $mustNotExists[] = $key;
                }
            }
            $action($builder, $mustExists, $mustNotExists);
        };

        return new static($name, $default, self::APPLICABLE_NOT_EMPTY, $action);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }
}
