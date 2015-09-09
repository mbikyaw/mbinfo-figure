# mbinfo-figure

Insert figure to MBInfo WordPress site.

Figure images are stored in Google Cloud Storage bucket.

## Using 

Use WordPress shortcode `mbinfo-figure` to display figure in box.

For example:

    [mbinfo-figure name="actin"] This is main text. Figure box will be the left.

Require attribute:

* name - figure page name

Optional attribute

* position - valid values are 'left' (default), 'right' and 'center'.
* size - valid values are 'small' (default), 'medium', 'large' and 'original'. 

## Setup

Set Google API Server key for MBInfo Figure plugin by using WP-CLI

    wp option set mbinfo-figure-gapi-key 'xxxx'
    
## Management
    
Use command line runner to manage images and maintenance. 

Loading image meta data from GCS to wordpress


    wp mbi-figure load

For detail, check out:
    
    wp help mbi-figure
    
## Testing

Setup your WP plugin test system by running

    bash bin/install-wp-test.sh
    
Create a file with name `credentials.json` withing following
    
    {
      "mbinfoFigureGapiKey": "xxx"
    }
    
Change `xxx` with Google API Server key.     
    
In the plugin folder, run `phpunit` unit test runner.    
    
    phpunit
    
