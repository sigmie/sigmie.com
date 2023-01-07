# Why are Search services expensive

Before diving deep into the Search world, I always wondered why services like Algolia and Enterprise Search by Elastic are so expensive.

Now let me tell you some reasons, why you may find the monthly subscription to those services expensive and how much it may cost you to maintain something similar on your own.

Searching efficiently is different than finding 1 **exact** word in a database. You also need to consider stopwords, stemming the query language, and other things like removing trailing spaces from a query string.

And there is another level of difficulty when searching for structured data like folder paths (eg. Main > Clothing > Shoes) or Email Addresses.

Except for the knowledge required you also need machines to run software that can do all this stuff. This may be Elasticsearch that’s running next to your application like your SQL database.

Now let’s say that the minimum price for a server running Elasticsearch is $20 and because you want to avoid any data loss in case of disaster you need 3 of those.

And we arrive at a $60 minimum for a decent setup for an e-shop. This number can grow faster than you think according to the number of records and requests.

To avoid the maintenance of your own Search Sigmie offers [shared plans](https://app.sigmie.com/sign-up). In shared plans you share **power** and **costs** with other customers.

It’s the ideal solution for buisnesses that want to give their users a nice search experience, but don’t want to do the maintenance themselves.
