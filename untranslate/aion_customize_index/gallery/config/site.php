<?php defined("NOVA") or die(); ?>
{
  "siteName": "Aionian Bible Image Gallery",
  "siteTitle": "Gallery Homepage",
  "metaTitle": "Aionian Bible Image Gallery",
  "metaDescription": "Aionian Bible Image Gallery",
  "footerText": "&copy; 2025 by novafacile OÜ",
  "theme": "novagallery",
  "url": "/gallery",
  "language": "en",
  "imagesDirName": "galleries",
  "storageDirName": "storage",
  "imageCache": true,
  "cacheFileListMaxAge": 60,
  "imageSizes": {
    "thumbnail": "400x400",
    "large": "2000"
  },
  "imageQuality": 85,
  "useOriginalForLarge": true,
  "useExifDate": true,
  "sortAlbums": "nameASC",
  "sortImages": "nameASC",
  "albumTitle": {
    "enabled": true,
    "transformation": "ucwords",
    "replace": {
      "_": " ",
      "-": " ",
      "/": " &raquo; "
    }
  },
  "imageCaption": {
    "enabled": true,
    "transformation": "ucwords",
    "replace": {
      "_": " ",
      "-": " "
    },
    "showInAlbum": true,
    "showInLightbox": false
  }
}