# Laravel Eagle Search

### what is Laravel Eagle Search. this library help you to filter data easily

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

##know how i can filter response data?

```
http://example.local:8000/api/accounts?filters[balance]=&|in<:>955300,2121500
```





