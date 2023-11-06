## Introduction
Let’s say you want to save a **Movie** in an SQL database. You first need to create a `users` table, then define columns like name **name** and **category**.  

**Then and only then** you can insert a movie to the table.

In Elasticsearch you just add the movie to the **Movies Index**.

You don’t need to create an Index, if it doesn’t exist it will be created.

Also as long as you keep the movie’s attributes under [1000](https://www.elastic.co/guide/en/elasticsearch/reference/7.17/mapping-settings-limit.html#mapping-settings-limit) you can have as many attributes as you want and you **don’t** need to define them first.

### What is an Index?
In a kid’s room, it’s a drawer that contains all the toys, in a database, it’s a table and in Elasticsearch it’s an **Index**.

The following is a simple representation of a `movies` **Index**. It’s **just a place where the movie records are stored** so that we can search for them in the future. 
```bash
Index
├─ Document 1
├─ Document 2
├─ Document 3
├─ ...
```

The records in an Index are called **Documents**.



### What is a Document?

Document is just a JSON stored in an Index.

```php
Document = JSON
```

```php
Index = Collection of related Documents 
```

Here’s an example of how a document may look like.

```json
{
   "name": "Alice in Wonderland"
}
```

```json
{
   "name": "Peter Pan"
}
```

```json
{
   "name": "Pinocchio"
}
```

and this is how we translate this Document into PHP code so that we can work with it.

```php
use Sigmie\Document\Document;

new Document(['name' => 'Mary Poppins']),
```

## Create an Index

Let’s create our first index using Sigmie. 

While there are a lot of options in the `NewIndex` builder class,
this is the simplest way to create an Index.

```php
$index = $sigmie->newIndex('movies')->create();
```

Once the above code is executed we have an **empty**  `movies` Index ready to get some Documents.

```bash
fantasy_characters
├─ # empty
```

### Add Documents
To add Documents to our Index we need to **collect** the Index. 

To do this we call the `collect` method on the Index instance returned from the Index builder `create` method. Then we pass an `array` of the Documents that we wish to add to the `merge` method.

Below is a showcase of how we add 3 Movies to the `movies` Index that we created before.

```php
use Sigmie\Document\Document;

$documents = [
    new Document(['name' => 'Mary Poppins']),
    new Document(['name' => 'The Mighty Ducks']),
    new Document(['name' => 'The Parent Trap']),
];

$index->collect()->merge($documents);
```

Usually, you won’t have an instance of the `Index` class. You can also `collect` an existing Index by calling the `collect` method on the Sigmie facade and passing the Index name.

```php
$sigmie->collect('movies')->merge($documents);
```

Here is what the Index looks like once we merge the Movie Documents.
```bash
fantasy_characters
├─ "Alice in Wonderland"
├─ "Peter Pan"
├─ "Pinocchio"
```

The process of adding Documents to an Index is called **Indexing**.

## Analysis
The truth is that the Index doesn’t store the **Documents** in the exact form that we indexed them. All Documents in an Index are **Analyzed** according to the Index settings.

This process is called **Analysis**. Let’s have a deeper look.

### What is Analysis?

The goal of Search Engines is to return **relevant** information **fast**. This is only possible if they do some work in advance. 

All **Text** fields of a Document are going through pre-defined **filters** that transform their texts.

### How are Documents analyzed?

If we create an `Index`  like this

```php
$sigmie->newIndex('movies')
    ->tokenizeOnWhitespaces() // [tl! highlight]
    ->lowercase() // [tl! highlight]
    ->create();
```

and index the bellow Documents

```php
[
    new Document(['name' => 'Mary Poppins']),
    new Document(['name' => 'The Mighty Ducks']),
    new Document(['name' => 'The Parent Trap']),
]
```

The `name` attribute of the Documents will look like this after it’s been **analyzed**.

```php
| Document 1   | Document 2  | Document 3  |
| -----------  | ----------- | ------------|
| "mary"       | "the"       | "the"       |
| "poppings"   | "mighty"    | "parent"    |
|              | "ducks"     | "trap"      |
```

This is because we added the 2 following steps to the Index **Analysis** proccess.
1. We called the `tokenizeOnWhitespaces` method. This caused the strings to split into **Tokens** each time a `whitespace` was encountered.
2. Next the `lowercase` method **changed all uppercase letters to lowercase**.

**These steps are done during indexing so they will be ready once a query hits our index.**

### How is the Query string analyzed?
Any incoming **Query string** is analyzed with the exact same filters as our Document **text** fields.

If we send the Query string `Mary` it will become `mary`  because of the `lowercase` token filter that we specified when creating our Index.
```php
| Query        | Analyzed Query |
| -----------  | -------------- |
| "Mary"       | "mary"         |
```

The logic is that now it doesn’t matter if the search user types `Mary`, `MARY`, or even `mArY` once the string is analyzed it will be `mary`.

### How does matching happen?
Now Elasticsearch looks into the analyzed values of the **Documents** to find which Documents contain the analyzed **query term**.

```php
| Term         | Document 1  | Document 2  | Document 3 |
| -----------  | ----------- | ------------|------------|
| "mary"       | x           |             |            |
```

Elasticsearch keeps also track of **How many times a term appears in a Document**, so the bellow table is a more accurate representation of the **matching** process.

```php
| Term         | Freq   | Occurrences |
| -----------  | ------ | ----------- |
| "mary"       | 1      | Document 1  |
```

You can find a deep explanation of the **Analysis** process in the **Analysis** section.

### How to test Analysis?

You can use the `analyze` method to find out how a given text is `analyzed` by your Index. You will get
an `array` containing the **tokens**.

```php
$tokens = $index->analyze('Mary Poppings'); // [ "mary", "poppings"]
```

## Index Update

Even though an Index update function doesn’t exist in Elasticsearch, we created an `update` function that you can use the **update** your Index.

```php
use Sigmie\Index\UpdateIndex;

$sigmie->index('movies')->update(function(UpdateIndex $updateIndex){

});
```

### How does the update work?
It’s important for you to understand the the `update` function does in the background. But before let’s see why an **Index update isn’t natively possible in Elasticsearch**.

### Why an Index update isn’t possible?

The reason is the **Analysis** process that makes the Index immutable. If an Index could be updated Elasticsearch would need to **analyze** the Documents all over again and there are too many risks and implications with this.

The only safe solution is to **create a new Index** and **reindex the Documents**.

### How is an Index created?
Luckily Elasticsearch supports Index **aliases** that allow us to update an Index without needing anyone to notice. 

When we create a `movies` Index like before. In the background, we create an Index with the name `movies` and a timestamp **suffix**. For example `movies_20221122210823379774`.

Then once the **Index** is created we assign it a `movies` alias that we can use instead of using the `movies_20221122210823379774`.

### How does Index update work?
In an update, we do the following 4 steps:
1. Create a new Index with a **different timestamp suffix**.
2. We **reindex** the Documents.
3. Remove the `movies` alias from the old Index.
4. We assign the `movies` alias to the new Index.

Here is a showcase of the 4 steps necessary for updating the `movies` Index.
#### Step 1 - Create a new index
```bash
movies (movies_20221122210823379774)
├─ "Mary Poppins"
├─ "The Mighty Ducks"
├─ "The Parent Trap"

movies_20221222210823379774 # [tl! add]
├─ # empty
```

#### Phase 2 - Reindex documents
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

#### Phase 3 - Swap Alias
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

#### Phase 4 - Delete old index
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


@danger
It’s important to keep in mind that the **Index Settings** aren’t merged with the old index. You need to always call the Index settings again in the `update` callback. 
@enddanger

## Delete an Index
You can delete an Index by calling the **delete** method on the Index instance.

```php
$sigmie->index('movies')->delete();
```

## Advanced
### Settings
Use the `config` method to add Index configurations from the [Index modules](https://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules.html).

```php
$newIndex->config('index.max_ngram_diff', 3);
```

### Shards
An advanced but commonly used term when speaking about Elasticsearch indices is **Shards**. There are thousands of resources on the Internet about **How many shards** you should pick for your Elasticsearch Index.

#### Defining shards

You can use the `shards` and `replicas` methods on the `NewIndex` builder class to pick your desired amount of shards.

```php
$sigmie->newIndex('movies')
    ->shards(1) // [tl! highlight]
    ->create();
```


#### What is a shard?

The best way to think of **Shards** is like **Smaller Search Engines inside an Index**. If you have an Index with **3 shards** and **8 Documents** they will be split like this.

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

#### How many Shards?
Typically keeping your shards size around 25-30 GB should be fine for most use cases.

### Replicas
**Replicas** or **Shard Replicas** are copies of your shards.

You can set the number of replicas with the `replicas` method.

```php
$sigmie->newIndex('movies')
    ->shards(1)
    ->replicas(2) // [tl! highlight]
    ->create();
```

#### Why do we need replicas?
Typically in a production environment, you will have an **Elasticsearch Cluster** with multiple nodes. Replica shards are required to keep failure tolerance.

Elasticsearch is smart and keeps shards distributed across the Cluster. 

Take a look at the following example with a 3-Node Cluster, having an Index with **3 Primary** shards and **2 Replicas**.

#### Shards behavior
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
In case of a node failure, we still have a copy of all the Documents in our Index. 

```sh
cluster
├─ server 1
│  ├─ primary 1
│  ├─ replica of primary 2
│  ├─ replica of primary 3
├─ server 2
│  ├─ primary 2
│  ├─ replica of primary 1
│  ├─ replica of primary 3
├─ server 3 # [tl! remove] 
│  ├─ primary 3 # [tl! remove] 
│  ├─ replica of primary 2 # [tl! remove] 
│  ├─ replica of primary 1 # [tl! remove] 
```

That’s because our Index consists of 3 shards, and even though a node containing some of our shards died. We still have a copy of those data on the other 2 servers.

#### What happens if two nodes die?
And also even if our 2nd server died, we still have 3 shards of our Index 2 copies, and 1 primary.
```sh
cluster
├─ server 1
│  ├─ primary 1
│  ├─ replica of primary 2
│  ├─ replica of primary 3
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

Even in an extremely unlikely scenario like this one, all our data are there and Elasticsearch can continue to serve our user’s searches until the 2 servers are up again. 

To make this clear, of course now the **replica** shards became **primary** shards and it looks like bellow.
```sh
cluster
├─ server 1
│  ├─ primary 1
│  ├─ primary 2
│  ├─ primary 3
```
