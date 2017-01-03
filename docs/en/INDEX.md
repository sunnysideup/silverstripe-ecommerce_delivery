E-commerce Delivery
==================================================

Adds delivery options to your e-commerce installation.

Developers
-----------------------------------------------
Nicolaas Francken [at] sunnysideup.co.nz

Requirements
-----------------------------------------------
see composer.json file

Project Home
-----------------------------------------------
See http://code.google.com/p/silverstripe-ecommerce

Demo
-----------------------------------------------
See http://www.silverstripe-ecommerce.com

Installation Instructions
-----------------------------------------------
 #. Find out how to add modules to SS and add module as per usual.
 #. Review configs and add entries to mysite/_config/config.yml (or similar) as necessary. In the _config/ folder of this module you can usually find some examples of config options (if any).

3. add to your `mysite/_config/ecommerce.yml`

```yml
Order:
  modifiers:
    - PickUpOrDeliveryModifier
```

Edit your database under admin/shop as you see fit (`http://mysite.co.nz/admin/shop/PickUpOrDeliveryModifierOptions`)

If you just want one or two things from this module
then of course you are free to copy them to your
mysite folder and delete the rest of this module.





