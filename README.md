## PLUGIN: WooCommerce Proxy Order On-Hold

1. Checks whether WooCommerce order has been placed via proxy/VPN using What's My IP API. 
2. If true, order is updated to custom status "follow up" (if status exists, which is checked) for investigation, else order is placed on hold for investigation. 
3. This plugin is used to further combat fraudulent orders being placed.
4. Support added in v1.0.1 for multiple API keys. 
5. API keys are rotated for each request sent so that max daily request limit is avoided.
6. Checks done on order thank you page via woocommerce_thankyou hook.
7. Adds order note stating that order was placed via proxy/VPN, with IP address provided.

NOTE: this is a bespoke plugin built for a client, so will likely require mods to fit your particular use case.
