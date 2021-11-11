## OpenPix for WooCommerce

## Installment Plugin

How to enable installment plugin

```php
private function includes()
{
    include_once dirname(__FILE__) . '/includes/class-wc-openpix-pix.php';
    include_once dirname(__FILE__) .
        '/includes/class-wc-openpix-installment.php';
}

public function add_gateway($methods)
{
    $methods[] = 'WC_OpenPix_Pix_Gateway';
    $methods[] = 'WC_OpenPix_Installment_Gateway';

    return $methods;
}
```
	

## LocalWP
You can run your Wordpress in many ways
Run directly in your machine
Run inside a docker compose setup

or

Using [LocalWP](https://localwp.com/) that will handle most of the complexity for you

## How to develop and install this plugin
Clone this repo inside wp-content/plugins

```jsx
cd wp-content/plugins
git clone https://github.com/Open-Pix/woo-openpix-plugin
```

## How it works?
It has a `woo-openpix-plugin.php` file that will render a basic html template and also inject css and javascript from our React app

## How to run
Start webpack and enjoy hot reload with fast refresh
```jsx
yarn start
````

## How to generate a new .zip version?

```shell
./pack.sh
```

## How to Release
```jsx
svn co https://plugins.svn.wordpress.org/woocommerce-openpix
cp Open-Pix/woocommerce-openpix content to svn woocommerce-openpix/trunk
unzip woocommerce-openpix.zip -d w1.1.0
cp w1.1.0/* woocommerce-openpix/thunk/.
M - means modified
svn ci -m "version 1.1.0"
svn cp trunk tags/1.1.0
```
