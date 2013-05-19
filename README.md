Note: If you don't need to use WordPress's native function, please consider using [this new WordPress bundle](https://github.com/kayue/KayueWordpressBundle) instead. The new bundle should provide faster performacne as it won't load the entire WordPress into Symfony, and also come with a complete WordPress entity repositories, better authenication system and multisite support.

Requirements
============

* WordPress 3.3.0 [revision 18993](https://core.trac.wordpress.org/changeset/18993) or higher
* Symfony 2.0.x

Usage
=====

Imagine you are in a Controller:

    class DemoController extends Controller
    {
        /**
         * @Route("/hello/{name}", name="_demo_hello")
         * @Template()
         */
        public function helloAction($name)
        {
            // retrieve the current user
            $user = $this->get('security.context')->getToken()->getUser();

            // retrieve user #2
            $user = new \WP_User(2);

            return array('username' => $user->user_login);
        }

        // ...
    }

Installation
============

1. Make sure WordPress's cookies are accessible from your Symfony 2 application. To confirm this,
   open up Symfony's profiler and look for `wordpress_test_cookie` inside the request tab.
   If you can't find the test cookie in request tab, please try to redefine the cookie path or
   domain used by WordPress by editing `wp-config.php`.
   For more information, please [read the WordPress Codex](http://codex.wordpress.org/Editing_wp-config.php)

        // wordpress/wp-config.php

        define('COOKIEPATH', '/' );
        define('COOKIE_DOMAIN', '.yourdomain.com');

2. Register the namespace `Hypebeast` to your project's autoloader bootstrap script:

        // app/autoload.php

        $loader->registerNamespaces(array(
              // ...
              'Hypebeast'    => __DIR__.'/../vendor/bundles',
              // ...
        ));

3. Add this bundle to your application's kernel:

        // app/AppKernel.php

        public function registerBundles()
        {
            return array(
                // ...
                new Hypebeast\WordpressBundle\HypebeastWordpressBundle(),
                // ...
            );
        }

4. Configure the WordPress service in your YAML configuration.

        # app/config/config.yml

        hypebeast_wordpress:
            wordpress_path: /path/to/your/wordpress

            # Set short_init to true if you only need the WordpressBundle to read user's login state,
            # this will make your application run faster by loading less Wordpress classes. It is
            # false by default.
            short_init: false

            # Set WordPress table prefix, default value is "wp_"
            table_prefix:   wp_

            # Your WordPress site's domain, it is requried to fix the redirection bug
            # when you have WordPpress multisite enabled. Optional in normal case.
            domain: example.com

5. Add the bundle factories, user provider, and authentication providers to your `security.yml`.
Below is a sample configuration. All of the options for the wordpress_* authentication methods are
optional and are displayed with their default values. You can omit them if you use the defaults,
e.g. `wordpress_cookie: ~` and `wordpress_form_login: ~`

        # app/config/security.yml

        security:

            # ...

            # Uncomment the following line in Symfony 2.0.
            # factories:
            #     - "%kernel.root_dir%/../vendor/bundles/Hypebeast/WordpressBundle/Resources/config/security_factories.xml"

            providers:
                wordpress:
                    entity: { class: Hypebeast\WordpressBundle\Entity\User, property: username }

            firewalls:
                secured_area:
                    pattern:    ^/demo/secured/
                    # Set to true if using WordPress's log out rather than Symfony's
                    # stateless:  true
                    wordpress_cookie:
                        # Set to false if you want to use a login form within your Symfony app to
                        # collect the user's WordPress credentials (see below) or any other
                        # authentication provider. Otherwise, the user will be redirected to your
                        # WordPress login if they need to authenticate
                        redirect_to_wordpress_on_failure: true

                    # Because this is based on form_login, it accepts all its parameters as well
                    # See the http://symfony.com/doc/2.0/cookbook/security/form_login.html for more
                    # details. Omit this if using WordPress's built-in login, as above
                    wordpress_form_login:
                        # This is the name of the POST parameter that can be used to indicate
                        # whether the user should be remembered via WordPress's remember-me cookie
                        remember_me_parameter: _remember_me

                    # You want your users to be able to log out, right? See Symfony docs for options
                    logout: ~

                    # anonymous:  ~

                # ...


6. When you're using the Symfony CLI, exclude the Wordpress tables by creating a default entitymanager

		# app/config/config.yml
		
        doctrine:
	        orm:
			    auto_generate_proxy_classes: %kernel.debug%
				    auto_mapping: true
		
will become
	
		doctrine:
			orm:
			    auto_generate_proxy_classes: %kernel.debug%
			    default_entity_manager:   default
			    entity_managers:
			        default:
			            connection:       default
			            mappings:
			                YourAppBundle: ~
		  

Multiple Blogs With Multiple Entity Manager
===========================================

```php
# app/config/config.yml

doctrine:
    orm:
        auto_mapping: false
        entity_managers:
            default:
                mappings:
                    AcmeDemoBundle: ~
                    # add all of your bundles here
            blog:
                mappings:
                    AcmeDemoBundle: ~
                class_metadata_factory_name: Acme\DemoBundle\ORM\Mapping\ClassMetadataFactory
```

```php
<?php
// Acme/DemoBundle/ORM/Mapping/ClassMetadataFactory.php

namespace Acme\DemoBundle\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataFactory as ClassMetadataFactoryInterface;

class ClassMetadataFactory extends ClassMetadataFactoryInterface
{
  public function getBlogId() {
    return 2;
  }
}
```

```php
<?php
// Acme/DemoBundle/Controller/DefaultController.php

public function indexAction()
{
  $em = $this->getContainer()->get('doctrine')->getEntityManager();
  $repo = $em->getRepository('HypebeastWordpressBundle:User');

  $repo->findAll();
}
```


Caveats
=======

* Because Symfony tracks the user's authentication state independently of WordPress, if the
  stateless is not set to true (see above) and the user logs out in WordPress, they will not be
  logged out of Symfony until they specifically do, or they end their session. To prevent this, you
  should use either Symfony's or WordPress's logout methods exclusively.
* WordPress assumes it will be run in the global scope, so some of its code doesn't even bother
  explicitly globalising variables. The required version of WordPress core marginally improves this
  situation (enough to allow us to integrate with it), but beware that other parts of WordPress or
  plugins may still have related issues.
* There is currently no user provider (use the API abstraction, see example above)
* Authentication errors from WordPress are passed through unchanged and, since WordPress uses HTML
  in its errors, the user may see HTML tags
