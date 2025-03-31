## Filtering 

Creating boolean queries can be complex, especially when your primary goal is to filter results.
To simplify this process, we've developed our own filtering language.

This language serves as a developer-friendly interface for constructing boolean queries, with the goal of reducing the likelihood of errors.

For instance, if you want to search for movies that are currently active, in stock, and belong to either the `action` or `horror` category, you can write:

```sql
is:active AND (category:"action" OR category:"horror") AND NOT stock:0
```

@warning
It's important to note that filtering isn't possible on **all** mapping types.

Visit the [Mapping](/docs/v0/mappings) section of this documentation for more details.
@endwarning

Now, let's delve deeper into the syntax and understand how to use it effectively.

### Syntax

Filter clauses can be combined using logical operators to create complex queries.

Here are the operators you can use:
* `AND`: Used to **combine two or more** filters. **Only documents that meet all conditions will be matched**.
* `OR`: Used to match documents that meet **at least one of the conditions**.
* `AND NOT`: Used to **exclude** documents that meet a certain condition.

Using these operators effectively will help you create precise and powerful filter queries.

Spaces are used to separate logical operators and filter clauses:

```bash
{filter_clause} AND {filter_clause}
```

To specify the order of execution, you can use **parentheses** to group clauses in a filter query.

Consider this example:

```sql
is:active AND (category:"action" OR category:"horror") AND NOT stock:0
```

Here, the **AND** operator joins three distinct clauses:
* `is:active`
* `(category:"action" OR category:"horror")`
* `NOT stock:0`

The parentheses indicate that the **OR** operator applies only to the category field clauses, not the entire query.

This query will return all items that are active, belong to either the "action" or "horror" categories, and are not out of stock.

### Negative Filtering

To construct a **negative** filter, prefix the filter value with `NOT`:

```bash
NOT {filter_clause}
```

For instance, to exclude documents in the "Sports" category:
```sql
NOT category:'Sports'
```

### Equals

```bash
{field}:"{value}"
``` 

This syntax filters for specific values. Use it when you need to narrow down your search based on a specific field value.

The `{field}` placeholder represents the field name. 

Consider this document structure:
```json
{
 "color": "..."
}
```

To filter for documents with the color red:

```sql
color:'red'
``` 

For strings, the `{value}` must be enclosed in double `"` or single quotes `'`.
You can escape quotes inside the `{value}` using a **backslash** `\`.

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

# Filtering Nested Properties

When working with nested properties in your Elasticsearch documents, you can use a special syntax to filter based on nested field values. The syntax supports both simple and complex nested property filtering.

## Basic Nested Property Filtering

To filter nested properties, use the following syntax:
```sql
property_name:{ field:"value" }
```

For example, if you have a nested field called `subject_services` with `id` and `name` properties:

```php
[
    'subject_services' => [
        ['name' => 'BMAT', 'id' => 23],
        ['name' => 'IMAT', 'id' => 24]
    ]
]
```

You can filter for specific values like this:
```sql
subject_services:{ id:"23" }
```

## Multiple Conditions in Nested Filters

You can combine multiple conditions within a nested filter using AND/OR operators:

```sql
subject_services:{ id:"23" AND name:"BMAT" }
```

## Deep Nested Properties

For deeply nested properties (nested fields within nested fields), you can use multiple levels of curly braces:

```
contact:{ address:{ city:"Berlin" AND marker:"X" } }
```

This would match documents with this structure:
```php
[
    'contact' => [
        'address' => [
            [
                'city' => 'Berlin',
                'marker' => 'X'
            ]
        ]
    ]
]
```

## Object Properties vs Nested Properties

It's important to note the difference between object properties and nested properties:

- For object properties, use dot notation:
```sql
contact.active:"true"
```

- For nested properties, use the curly brace syntax:
```sql
contact:{ active:"true" }
```

## Combining Nested Filters

You can combine nested filters with other filter types using AND/OR operators:

```sql
subject_services:{ id:"23" } AND category:"active"
```

Remember that nested filters are powerful but should be used judiciously, as they can impact query performance, especially with deeply nested structures or complex conditions.

# Geo-Location Filtering

Elasticsearch provides powerful geo-location filtering capabilities that allow you to search for documents within a specific distance from a given point. 

## Basic Syntax

The basic syntax for geo-location filtering is:
```
location:distance[latitude,longitude]
```

Where:
- `location` is your geo-point field name
- `distance` is the radius with a unit (see supported units below)
- `latitude` and `longitude` are the coordinates of the center point

## Distance Units

You can specify distances using various units:
- Kilometers: `km`
- Miles: `mi`
- Meters: `m`
- Yards: `yd`
- Feet: `ft`
- Nautical Miles: `nmi`
- Centimeters: `cm`
- Inches: `in`

Examples:
```
location:70km[52.31,8.61]
location:5mi[-33.8688,151.2093]
location:100m[40.7128,-74.0060]
location:500yd[35.6762,139.6503]
location:1000ft[55.7558,37.6173]
location:10nmi[-22.9068,-43.1729]
location:50cm[-1.2921,36.8219]
location:3in[41.9028,12.4964]
```

## Document Structure

Your documents should store geo-points in this format:
```php
[
    'location' => [
        'lat' => 51.16,
        'lon' => 13.49
    ]
]
```

## Example Usage

To find documents within 1 kilometer of a specific point:
```
location:1km[51.49,13.77]
```

## Combining with Other Filters

You can combine geo-location filters with other filters using AND/OR operators:
```
location:1km[51.49,13.77] AND is:active
```

## Important Notes

1. Distance of Zero:
   - Using `location:0km[lat,lon]` will not return any results, even for exact matches
   - Always use a small positive distance for exact location matching

2. Precision:
   - You can use decimal points in coordinates for more precise locations
   - Example: `location:2km[51.16,13.49]` vs `location:2km[51,13]`

3. Performance:
   - Geo-location queries can be computationally expensive
   - Consider using appropriate distances based on your use case
   - Very large distances (like `2000000000mi`) might impact performance

## Nested Geo-Location Filters

You can also use geo-location filters with nested fields:
```
contact:{ location:1km[51.16,13.49] }
```

For a document structure like:
```php
[
    'contact' => [
        'location' => [
            'lat' => 51.16,
            'lon' => 13.49
        ]
    ]
]
```

Remember that geo-location filtering is particularly useful for:
- Finding nearby locations
- Territory-based searches
- Distance-based filtering
- Geographic boundary queries
