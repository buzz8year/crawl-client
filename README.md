PHP Crawling Client
---------
- Morphology
- Cron friendly (linux)
- Console/web interfaces
- VM/screen friendly
- OOP


Structure
---------

```
common/                  default yii2 files  
console/                 default yii2 files  
    controllers/         console controller class (implementing methods to be used within console/terminal)
    ...    
backend/                 default yii2 advanced-template files
    controllers/         controller classes for web-interface
    models/              contains specific model (or helper) classes
            captcha/     helper class to process captchas
            morph/       helper class to work with phpmorphy
            parser/      main crawling-logic classes & oop-interfaces  
                source/  directory for new crawl-source interface implemetations (only 4 mandatory methods)
                ...
            search/      search helpers
            sync/        helper class to update aggregator (marketplace) db 
            synonym/     helper class to work with synonymizers
            ...            
frontend/                default yii2 files (and logic for your aggregator/marketplace)
vendor/                  dependencies
environments/            default yii2 files
...
```
