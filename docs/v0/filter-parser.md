## Filtering 

Creating boolean queries can be complex, especially when your primary goal is to filter results.
To simplify this process, we've developed our own filtering language.

This language serves as a developer-friendly interface for constructing boolean queries, with goal to reduce the likelihood of errors.

For instance, if you want to search for movies that are currently active, in stock, and belong to either the `action` or `horror` category, you can write:

```sql
is:active AND (category:"action" OR category:"horror") AND NOT stock:0
```

@warning
It’s important to note here, filtering isn’t possible on **all** mapping types.


Visit the [Mapping](/docs/v0/mappings) section of this documentation for more.
@endwarning

Now, let's delve deeper into the syntax and understand how to use it effectively.

### Syntax

Filter clauses can be combined using logical operators to create complex queries.

Here are the operators you can use:
* `AND`: This operator is used to **combine two or more** filters. **Only documents that meet all the conditions will be matched**.
* `OR`: This operator is used to match documents that meet **at least one of the conditions**.
* `AND NOT`: This operator is used to **exclude** documents that meet a certain condition.

Using these operators effectively will help you create precise and powerful filter queries.

Spaces are used to separate logical operators and filter clauses.

```bash
{filter_clause} AND {filter_clause}
```

To specify the order of execution, **parentheses** can be used to group clauses in a filter query.

Consider this example:

```sql
is:active AND (category:"action" OR category:"horror") AND NOT stock:0
```
Here, the **AND** operator is used to join three distinct clauses:
* `is:active`
* `(category:"action" OR category:"horror")`
* `NOT stock:0`

The parentheses indicate that the **OR** operator is applicable to the category field clauses, not the entire query.

This query will return all items that are active, belong to either the "action" or "horror" categories, and are not out of stock.

### Negative Filtering

To construct a **negative** filter, prefix the filter value with `NOT`.

```bash
NOT {filter_clause}
```
For instance, if you wish to exclude documents in the "Sports" category, you would construct a filter as follows:
```sql
NOT category:'Sports'
```

### Equals

```bash
{field}:"{value}"
``` 
The syntax above is used to filter for specific values. This operator is beneficial when you need to narrow down your search based on a specific field value.

The `{field}` placeholder represents the field name. 

Consider this document structure:
```json
{
 "color": "..."
}
```

To filter for documents with the color red, you would use:

```sql
color:'red'
``` 
For strings, the `{value}` must be enclosed in double `"` or single quotes `'`.
You can escape quotes inside of the `{value}` using a **backslash** `\`.

### is & is_not

The `is:` and `is_not:` operators are used to filter `boolean` fields.

The `is` operator matches documents where the specified **boolean** field value is `true`.

```bash
is:{field}
```

Conversely, the `is_not` operator matches documents where the specified **boolean** field value is `false`.

```bash
is_not:{field}
```

For instance, consider the following document structure:
```json
{
  "active": true
}
```

In this case, you can use:

```sql
is:active
```

to match documents that are **active**, and:

```sql
is_not:active
```

to match documents that **are NOT** active.

### In

The `in` operator is useful when you need to filter a field for multiple values.

```bash
{field}:[{value1}, {value2}]
```

For instance, given the previous document structure, if you want to find documents with the **color** `red` or `blue`, you can use:

```bash
category:['red', 'blue']
```

### Range Filtering

Often, you may need to filter data within a certain range. This can be achieved using the **range operators**.

Here is a list of **valid** range operators and their corresponding meanings:
*  `>` - **greater than**.
* `<` - **less than**.
* `<=` - **less than or equal to**.
* `>=` - **greater than or equal to**.

The syntax for using **range operators** is as follows:

```bash
{field}{operator}{value} 
```

**Range operators** can be used for both **Dates** and **Numbers**.

For instance, consider the following document structure:
```php
{
 "created_at": "2023-08-01",
 "price": 199
}
```
By using the filter:
```sql
created_at>="2023-05-01" AND created_at<="2023-10-01"
```
you can filter documents where the `created_at` date field is **greater than or equal to** `2023-05-01` and **less than or equal to** `2023-10-01`.

The same syntax can be used to filter within a specific **price range**.

```sql
price>=100 AND price<=200
```


## Sorting

To further refine your search results, you can use our intuitive sorting language.

Here's how you can use it:

```bash
_score rating:desc name:asc
```

In this example, the results are first sorted by the relevance score, then by rating in a descending order,
and lastly by name in an ascending order.

@info
The `_score` is a unique sorting attribute that arranges the results in a descending order,
based on their computed relevance score.
@endinfo

Sorting clauses are divided by spaces, and follow this syntax:
```sql
{attribute}:{direction}
```

The `direction` can be either `asc` for ascending order or `desc` for descending order.
