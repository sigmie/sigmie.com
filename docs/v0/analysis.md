## Introduction
The analysis is the process that separates Elasticsearch from a traditional database. To accomplish this impressive speed when searching one word in thousands of records, work must be done before the search happens.

Every Document Text field is **analyzed** with its corresponding **Analyzer** at index time. This process is called **Analysis** and it consisted of the 3 steps below. 

```bash
Analysis
├─ Char filters
├─ Tokenizer
├─ Token filters
```

Once the text passes through all the steps it has a form efficient for searching.

## Analyzer
The Analyzer is responsible for performing the **Analysis**. It’s a group of **Char filters**, a **Tokenizer**, and **Token filters**. 

The Document Text field either has its own **Analyzer** or uses the Index **default** one.

Let’s see an example of how this HTML Text

```php
"<span>Some people are worth melting for.</span>"
```

is analyzed by the below **Analyzer**.
```bash
Analyzer
├─ Char filters
│  ├─ Strip HTML
├─ Tokenizer
│  ├─ Word Boundaries
├─ Token filters
│  ├─ Lowercase
```


### Char filter
The first step in the Analysis is to apply the configured **Char Filters**. In our case, the `Strip HTML` char filter removes all HTML from the text.
```php
"<span>Some people are worth melting for.</span>"  // [tl! remove]
"Some people are worth melting for."               // [tl! add]
```

### Tokenize
After the **Char Filters** the resulting string is passed to the **Tokenizer** that split’s the text into terms called **tokens**.

In our example, we have the **Word Boundaries** tokenizer. This means that the tokenizer will produce a token every time it encounters a **word boundary** like this.
```php
"Some people are worth melting for."               // [tl! remove]
"Some"                                             // [tl! add]
"people"                                           // [tl! add]
"are"                                              // [tl! add]
"worth"                                            // [tl! add]
"melting"                                          // [tl! add]
"for"                                              // [tl! add]
```

### Token filters
The last step in the **Analysis** is to apply the **Token Filters** to all **tokens** produced by the tokenizer. Our example has the **Lowercase** token filter that converts all tokens to only contain **lowercase** letters.
```php
"Some"                                             // [tl! remove]
"some"                                             // [tl! add]
"people"                                          
"are"                                             
"worth"                                           
"melting"                                         
"for"                                             
```

## Query
Every time a query hits the Index, the **query string** goes through the same analysis process.

Once both the **Query String** and the **Document** attribute are analyzed in the same way, it’s easier for Elasticsearch to find where the incoming terms appear. 
```php
| Term         | Document 1  | Document 2  |
| -----------  | ----------- | ------------|
| "some"       | x           | x           |
| "people"     | x           | x           |
| "are"        | x           | x           |
| "worth"      | x           | x           |
| "melting"    |             | x           | // [tl! highlight]
| "for"        | X           | x           |
```
