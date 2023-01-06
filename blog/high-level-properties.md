# You do NOT need to know Elasticsearch to use Elasticsearch
High-level mapping properties that are optimized for the data that they are representing.

In programming you have field types called **strings**, that can be any possible text combination in the world.
* A Product Color
* A Country Name
* An Address
* Tags

String is simply too generic, especially in a business context.

Take for example the **name** field of a product data structure from an online shop.

The same **name** field has a customer in an insurance company database.

So since strings can mean many things, we decided to narrow `string` types down to more expressive fields.

Instead of using `string`  or `text`, we created the following:
* Name
* Address
* HTML
* Category

Take **Category** for example, in an E-Shop, it can be the product **Color** or **Type** like shoes, jeans, or hats.

Where in an Insurance company database it can be a **Contract Type**.

This way we can know how a specific attribute of your indexed Documents should be queried for the best results.
