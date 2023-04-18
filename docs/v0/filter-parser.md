## Introduction

Writing boolean queries is daunting when all that you want is to filter.
That's why we created our own Elasticsearch filtering language
that servers as syntactical sugar for constructing boolean queries.

It's a abstraction for writing complex **boolean** queries using a sympler syntax
that aims to make the proceess more intuitive and less error-prone.

For example, you can filter search for  movies that are **active**, **in stock** and have the category
**action** or **horror** simply writing:

```sql
is:active AND (category:"action" OR category:"horror") AND NOT stock:0
```

**It's important to note here, filtering isn't possible on mapping types. Visit the Mapping section of this documentation for more.**

Let's have a deeper look on the available syntax

## Syntax

The filter clauses are combined using the below logical operators to create complicated queries.
* `AND`
* `OR`
* `AND NOT`

The `AND` operator is used to combine two or more filers so that only documents that satisfy all the conditions are matches.

The `AND NOT` operator is used to exclude documents that match a certain condition.

The `OR` operator is used to match documents that satisfy at least one of the conditions.

Logical operators and filter clauses are separated with a space.

```bash
{filter_clause} AND {filter_clause}
```

You can use **parentheses** to group clauses in a filter query, to specify the order in which they are executed.

For example:

```sql
is:active AND (category:"action" OR category:"horror") AND NOT stock:0
```
the **AND** operator is used to join tree separate clauses
* `is:active`
* `(category:"action" OR category:"horror")`
* `stock:0`

The parentheses indicate that the **OR** operator applies to the category field
clauses and not to the entire query.

This query will return all items that are active, belong to either the "action" or the "horror" categories, and have a stock greater than zero.

## NOT

To create a **negative** filter you can prefix the filter value with `NOT`.

```bash
NOT {filter_clause}
```
with this in mind if you want to filter out let's say documents that are in the "Sports" category you will write a filter like this:
```sql
NOT category:'Sports'
```

### Equals

```bash
{field}:"{value}"
``` 
The above syntax is used to filter for concrete values. This operator is useful when you want to filter your search absed on a particular field value.

The `{field}` placeholder represents the field name. 

Imagine this document structure:
```json
{
 "color": "..."
}
```

To filter for the documents, which color is red you will use

```sql
color:'red'
``` 
For strings the `{value}` needs to be enclosed inside of double `"` or single quotes `'`.
You can escape quotes inside of the `{value}` using a **backslash** `\`.

### is & is_not

To filter `boolean` fields use the `is:` and `is_not:`

The `is` operator is used to find documents where the **boolean** field value is `true`.

```bash
is:{field}
```

The `is_not` operator is used the filter documents where the **boolean value is** `false`.

```bash
is_not:{field}
```

In the documents stucture
```json
{
  "active": true
}
```

we will use

```sql
is:active
```

to match documents that are **active** and the

```sql
is_not:active
```

to match documents that **are NOT** active.

### In

You can use the `in` operator if you need to filter for multiple values in a field.

```bash
{field}:[{value1}, {value2}]
```

Consider for the document structure from above that to find documents, that
have the **color** `red` and `blue` you can use:

```bash
category:['red', 'blue']
```

### Range

It's common that you will need to filter various ranges. You can accomplish
this using the **range operators**.

This is the list with the **valid** range operators and their
meaning.
*  `>` **greater than**.
* `<` **less than**.
* `<=` **less or equal** to.
* `>=` **greater or equal** to.

You can use **range operators** with the following syntax.

```bash
{field}{operator}{value} 
```

It's possible use the **range operators** for **Dates** and for **Numbers**.

For example, think of the following structure
```php
{
 "created_at": "2023-08-01",
 "price": 199
}
```
using the filter
```sql
created_at>="2023-05-01" AND created_at<="2023-10-01"
```
you can filter documents that the `created_at` date field is **greater or equal** to `2023-05-01` and **less or equal** to `2023-10-01`.

You can use the same syntax for filtering the **price range**.

```sql
price>=100 AND price<=200
```

To use the above syntax call the `filter` method on the **search builder** instance.


## Parser

You can also use the filter parser wihout the search builder, by creating an instance oof the `FilterParser`.

To initialize the `FilterParser` you need to pass and instance of your `Properties` class to the constructor. The parser need this to correctly determindate the property types and if they are filterable.

Once you have an instance of the `FilterParser` you can call the `parse` method and pass the filter string to it.

Consider the following example:

```php
$props = new Properties();
$props->category();

$parser = new FilterParser($props);

$jsonFilters== (object) $parser->parse('category:'Action')->toRaw();
```

the resulting JSON variable will look like this:

```json
{
    "bool": {
        "must": [
            {
                "term": { // [tl! add]
                    "category.raw": { // [tl! add]
                        "value": "action", // [tl! add]
                        "boost": 1 // [tl! add]
                    } // [tl! add]
                } // [tl! add]
            }
        ],
        "boost": 1
    }
}
```

The above can be nicely combined with the query templates like this

```php
$response = $sigmie->template(id: $search)->run($index, [
  'filters' => $jsonFilters,
  'query_string' => $query,
]);

```

