# files_mediaviewer
Viewer for pictures and videos integrated in the files app

![2018-12-12 13-08-17 2018-12-12 13_09_44](https://user-images.githubusercontent.com/12717530/49868973-43303f00-fe0f-11e8-8f7a-1a5460f51fcd.gif)

## Building

Requires
* Node.js
* yarn

Run `yarn install && yarn build` to build.

## Supporting more media types

First, make sure you have installed ImageMagick and its imagick PECL extension.
Next add a few new entries to your **config/config.php** configuration file.

```
  'preview_max_scale_factor' => 1,
  'enabledPreviewProviders' =>
  array (
    0 => 'OC\\Preview\\PNG',
    1 => 'OC\\Preview\\JPEG',
    2 => 'OC\\Preview\\GIF',
    11 => 'OC\\Preview\\Illustrator',
    12 => 'OC\\Preview\\Postscript',
    13 => 'OC\\Preview\\Photoshop',
    14 => 'OC\\Preview\\TIFF'
  ),
```

Look at the sample configuration (config.sample.php) in your config folder if you need more information about how the config file works.
That's it. You should be able to see more media types in your slideshows and galleries as soon as you've installed the app.
