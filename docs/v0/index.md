# Index


## Introduction

### What is an Index?
```bash
movies
├─ Document 1
├─ Document 2
├─ Document 3
├─ ...
```


### What is a Document?

```php
Document = JSON
```

```json
{
   "name": "Mary Poppings"
}
```

```php
new Document(['name' => 'Mary Poppins']),
```

## First Index

### Create an Index
```php
$index = $sigmie->newIndex('movies')->create();
```

```bash
movies
├─ # empty
```

### Add Documents
```php
$documents = [
    new Document(['name' => 'Mary Poppins']),
    new Document(['name' => 'The Mighty Ducks']),
    new Document(['name' => 'The Parent Trap']),
];

$index->collect()->merge($documents);
```

```bash
movies
├─ "Mary Poppins"
├─ "The Mighty Ducks"
├─ "The Parent Trap"
```

## Analysis

### What is Analysis?

Without going into details the index analyzes the given text according
to the filters that your specify.

To understand how it's done visit the analysis section. So if create an index like this 

```php
$sigmie->newIndex('disney')
    ->tokenizeOnWhitespaces()
    ->lowercase()
    ->create();
```

the four movies for above are as follow the `name` is splited
on `whitespaces` into tokens and then the tokens are `lowercased`.

Resulting to the following.

### How are Documents analyzed?
```php
| Document 1   | Document 2  | Document 3  |
| -----------  | ----------- | ------------|
| "mary"       | "the"       | "the"       |
| "poppings"   | "mighty"    | "parent"    |
|              | "ducks"     | "trap"      |
```

So if we search for `Mary` the index returns the document
that containts query string.

The same filters are also applied to the query string and in this
case `Mary`  becomeds `mary`.

### How is the Query string analyzed?
```php
| Query        | Analyzed Query |
| -----------  | -------------- |
| "Mary"       | "mary"          |
```

Now the search returns the Documents that contains this term.

```php
| Query        | Document 1  | Document 2  | Document 3 |
| -----------  | ----------- | ------------|------------|
| "mary"       | x           |             |            |
```

You can find more information in the **Analysis** section about in detail
how this process works and all it's available methods.

### How to test Analysis?
```php
$tokens = $index->analyze('Mary Poppings'); // [ "mary", "poppings"]
```

### Index Update

```php
use Sigmie\Index\UpdateIndex;

$sigmie->index('movies')->update(function(UpdateIndex $updateIndex){

    // update

});
```

@danger
You need to add all the analysis methods since they are not merged.
@enddanger

## Delete an Index
```php
$sigmie->index('movies')->delete();
```

## How update works

### Phase 1 - Create new index
```bash
movies (movies_20221122210823379774)
├─ "Mary Poppins"
├─ "The Mighty Ducks"
├─ "The Parent Trap"

movies_20221222210823379774 # [tl! add]
├─ # empty
```

### Phase 2 - Reindex documents
```bash
movies (movies_20221122210823379774)
├─ "Mary Poppins"
├─ "The Mighty Ducks"
├─ "The Parent Trap"

movies_20221222210823379774
├─ "Mary Poppins" # [tl! add]
├─ "The Mighty Ducks" # [tl! add]
├─ "The Parent Trap" # [tl! add]
```

### Phase 3 - Swap Alias
```bash
movies_20221122210823379774
├─ "Mary Poppins"
├─ "The Mighty Ducks"
├─ "The Parent Trap"

movies (movies_20221222210823379774) # [tl! add]
├─ "Mary Poppins"
├─ "The Mighty Ducks"
├─ "The Parent Trap"
```

### Phase 4 - Delete old index
```bash
movies_20221122210823379774 # [tl! remove]
├─ "Mary Poppins" # [tl! remove]
├─ "The Mighty Ducks" # [tl! remove]
├─ "The Parent Trap" # [tl! remove]

movies (movies_20221222210823379774) # [tl! add] 
├─ "Mary Poppins"
├─ "The Mighty Ducks"
├─ "The Parent Trap"
```

## Shards

### Defining shards
```php
$sigmie->newIndex('disney')
    ->shards(1) // [tl! highlight]
    ->replicas(2) // [tl! highlight]
    ->create();
```
### What is a shard?
```bash
movies
├─ shard 1
│  ├─ document 1
│  ├─ document 2
│  ├─ document 3
├─ shard 2
│  ├─ document 4
│  ├─ document 5
│  ├─ document 6
├─ shard 3
│  ├─ document 7
│  ├─ document 8

```

### Thumb rules
* 25-30 GB

### Shards behavior
```bash
cluster
├─ server 1
│  ├─ primary 1
│  ├─ replica of primary 2
│  ├─ replica of primary 3
├─ server 2
│  ├─ primary 2
│  ├─ replica of primary 1
│  ├─ replica of primary 3
├─ server 3
│  ├─ primary 3
│  ├─ replica of primary 2
│  ├─ replica of primary 1
```

#### What happens if a node dies?
```sh
cluster
├─ server 1
│  ├─ primary 1
│  ├─ primary 3 # [tl! add] 
│  ├─ replica of primary 2 # [tl! add] 
│  ├─ replica of primary 2
│  ├─ replica of primary 3
├─ server 2
│  ├─ primary 2
│  ├─ replica of primary 1
│  ├─ replica of primary 1 # [tl! add] 
│  ├─ replica of primary 3
├─ server 3 # [tl! remove] 
│  ├─ primary 3 # [tl! remove] 
│  ├─ replica of primary 2 # [tl! remove] 
│  ├─ replica of primary 1 # [tl! remove] 
```

#### What happens if two nodes die?
```sh
cluster
├─ server 1
│  ├─ primary 1
│  ├─ primary 3 # [tl! add] 
│  ├─ replica of primary 2 # [tl! add] 
│  ├─ replica of primary 2
│  ├─ replica of primary 3
│  ├─ primary 2 # [tl! add]
│  ├─ replica of primary 1 # [tl! add]
│  ├─ replica of primary 1 # [tl! add] 
│  ├─ replica of primary 3 # [tl! add]
├─ server 2 # [tl! remove] 
│  ├─ primary 2 # [tl! remove] 
│  ├─ replica of primary 1 # [tl! remove] 
│  ├─ replica of primary 1 # [tl! remove] 
│  ├─ replica of primary 3 # [tl! remove]
├─ server 3 # [tl! remove] 
│  ├─ primary 3 # [tl! remove] 
│  ├─ replica of primary 2 # [tl! remove] 
│  ├─ replica of primary 1 # [tl! remove] 
```
