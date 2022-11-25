# Laravel Eagle Search

### what is Laravel Eagle Search 🤔 . eagle search library help you to filters your data and order your data easily

How to install it

```
composer require devazizi/laravel-eagle-search
```

add **EagleSearchTrait** to your models

```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LaravelEagleSearch\EagleSearchTrait;

class Account extends Model
{
    use HasFactory, EagleSearchTrait;

    public $searchable = [
        'balance' => 'balance',
        'card_no' => 'creditCards.credit_card_number'
    ];

    protected $fillable = ['user_id', 'balance', 'account_number'];

    public function creditCards()
    {
        return $this->hasMany(CreditCard::class);
    }
}

```

know your model prepared to filter data you most add **setFilters** method into in your query

```
return \App\Models\Account::query()->setFilters()->get();
```

## know how i can filter response data?

```
http://example.local:8000/api/accounts?filters[balance]=&|in<:>955300,2121500
```

# for orders columns. How you can this 😉, add EagleOrderTrait trait in your models

```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LaravelEagleSearch\EagleOrderTrait;
use LaravelEagleSearch\EagleSearchTrait;

class Account extends Model
{
    use HasFactory, EagleSearchTrait, EagleOrderTrait;

    public $searchable = [
        'balance' => 'balance',
        'card_no' => 'creditCards.credit_card_number'
    ];

    public $orderable = [
        'balance',
        'id'
    ];

    protected $fillable = ['user_id', 'balance', 'account_number'];

    public function creditCards()
    {
        return $this->hasMany(CreditCard::class);
    }
}
```

#### after adding EagleOrderTrait in your model you can use setOrders methods in your queries

``
return \App\Models\Account::query()->setFilters()->setOrders()->get();
``

add your required fields for sorting as query string

``
http://example.local:8000/api/search?orders[id]=desc&orders[balance]=asc
``

# table of searching facilities

| in sql      | eagle search | description           |
|-------------|--------------|-----------------------|
| =           | eq           ||
| !=          | !eq          |                       |
| in          | in           ||
| not in      | !in          ||
| between     | btw          ||
| not between | !btw         ||
| is null     | nil          ||
| is not null | !nil         ||
|             | gte          | greater or equal than |
|             | gt           | greater than          |
|             | lte          | less or equal than    |
|             | lt           | less than             |
