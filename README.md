## Installation

### Install the bundle 

Execute the following [composer](https://getcomposer.org/) command to add the bundle to the dependencies of your project:

```bash
composer require sunsetbeat/sulu-linkedin-feed
```

### Enable the bundle 
 
 Enable the bundle by adding it to the list of registered bundles in the `config/bundles.php` file of your project:
 
#### *is executed automatically !!!*

 ```php
 return [
    /* ... */
    sunsetbeat\SuluLinkedinFeed\SuluLinkedinFeedBundle::class => ['all' => true],
 ];
 ```


## Execute

Console command to import LinkedIn-Feed data:

```bash
bin/console sunsetbeat:import-social-media-feed # All
bin/console sunsetbeat:import-social-media-feed --interface linkedin_feed # Only LinkedIn Feed
bin/console sunsetbeat:import-social-media-feed --interface linkedin_images # Only LinkedIn Images
```


### Update schema

```shell script
bin/console doctrine:schema:update --force
```

## Bundle Config
    
Define the Admin Api Route in `routes/sulu_linkedin_feed.yaml`
```yaml
sunsetbeat_sulu_linkedin_feed:
    resource: '@SuluLinkedinFeedBundle/Controller/'
    type: attribute
```

Define the settings in `packages/sulu_linkedin_feed.yaml`
```yaml
sunsetbeat_sulu_linkedin_feed:
    menu:
        main_menu: 10
        sub_menu:
            linkedin_feed: 10
```

## Add block

Insert into `/config/templates/pages/blocks.xml`:

```xml
<block name="blocks" default-type="text" minOccurs="0">
    <types>
        <type ref="sunsetbeat_sulu_linkedin_feed_smartcontent" />
    </types>
</block>
```

## Role Permissions
If this bundle is being added to a previous Sulu installation, you will need to manually add the permissions to your admin user role(s) under the `Settings > User roles` menu option.

## Update ".env"-files

Add following lines to ".env"-file:

```bash
SUNSETBEAT_SULU_LINKEDIN_FEED_ACCESSTOKEN='#####'
SUNSETBEAT_SULU_LINKEDIN_FEED_TYPE='author'
SUNSETBEAT_SULU_LINKEDIN_FEED_TYPE_ID='#####' #urn:li:organization:#####
SUNSETBEAT_SULU_LINKEDIN_FEED_AMOUNT='10'
```