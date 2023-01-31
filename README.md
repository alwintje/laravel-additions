# Laravel additions

### Composite primary key

Use the [HasCompositePrimaryKey](src/Models/Concerns/HasCompositePrimaryKey.php) trait when using a composite primary key.


### Advanced relations

Use the trait AdvancedRelationships to add advanced relations in your model.
For these relation types are advancements made:
* [HasMany](src/Models/Relations/HasMany.php)
* [HasOne](src/Models/Relations/HasOne.php)
* [HasOneByMultipleFields](src/Models/Relations/HasOneByMultipleFields.php)
* [HasManyThroughByMultipleFields](src/Models/Relations/HasManyThroughByMultipleFields.php)
* [HasOneThroughByMultipleFields](src/Models/Relations/HasOneThroughByMultipleFields.php)

:warning: **Probably not all relation methods are working proper** - I only made what I needed

#### Example

Suppose you got a website for multiple domains: siteA.com and siteB.com and you want a many-to-many relation for a specific domain. You have the following tables categories, category_parents, products. 
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kroesen\LaravelAdditions\Models\Concerns\AdvancedRelationships;
use Kroesen\LaravelAdditions\Models\Concerns\HasCompositePrimaryKey;
use Kroesen\LaravelAdditions\Models\Relations\HasMany;
use Kroesen\LaravelAdditions\Models\Relations\HasOneThroughByMultipleFields;
use Kroesen\LaravelAdditions\Models\Relations\HasManyThroughByMultipleFields;
use Kroesen\LaravelAdditions\Models\Relations\HasOneByMultipleFields;

class Category extends Model
{
    
    use HasCompositePrimaryKey, AdvancedRelationships;
    
    protected $primaryKey = ['id', 'domain'];
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'domain',
        'name',
    ];
    
    public function parent(): HasOneThroughByMultipleFields
    {
        return $this->hasOneThroughByMultipleFields(
            Category::class,
            CategoryParent::class,
            ['id' => 'category_id', 'domain' => 'domain'],
            ['id' => 'parent_id', 'domain' => 'domain'],
            function($query){
                // Do something with the query
            }
        );
    }
    
    public function children(): HasManyThroughByMultipleFields
    {
        return $this->hasManyThroughByMultipleFields(
            Category::class,
            CategoryParent::class,
            ['id' => 'parent_id', 'domain' => 'domain'],
            ['id' => 'category_id', 'domain' => 'domain'],
            function($query){
                // Do something with the query
            }
        );
    }
    
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'id', function ($query){
            // Do something with the query
        });
    }

}

class CategoryParent extends Model
{
    
    use HasCompositePrimaryKey, AdvancedRelationships;
    
    protected $primaryKey = [
        'category_id',
        'parent_id',
        'domain',
    ];
    
    public $incrementing = false;
    public $timestamps = false;
    
    
    protected $fillable = [
        'category_id',
        'parent_id',
        'domain',
    ];

    public function parent(): HasOne
    {
        return $this->hasOneByMultipleFields(Category::class, [
            'parent_id' => 'id',
            'domain' => 'domain',
        ]);
    }

    public function category(): HasOne
    {
        return $this->hasOneByMultipleFields(Category::class, [
            'category_id' => 'id',
            'domain' => 'domain',
        ]);
    }
}
```
