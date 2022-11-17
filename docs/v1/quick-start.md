# Quick start

Generate authentication keys to authenticate your API.

## Types

There are 2 API Key types that you have to use to **authenticate** with your
Sigmie Application.

Each type has different permission and is intended for different use.

### Admin

Admin token has full power over your Sigmie Application documents, meaning that using
an **Admin** token, you can create, update or delete any Documents.

This API Token is intended to be used on the Server-Side of your application.

To generate such Key choose **Admin** in the `Type` dropdown selection when
creating an API Key.

 

{% callout type="danger" title="Secret" %}
Never expose the Admin Keys to the user.
{% /callout %}

### Search

Using **Search** tokens you can make [Search Request](/docs/api/search) to your
**Sigmie Application**.

To generate such Key choose **Search** in the `Type` dropdown selection when
creating an API Key.

{% callout type="info" title="Frontend" %}
You can safely expose this Key in the frontend part of
your application.
{% /callout %}
