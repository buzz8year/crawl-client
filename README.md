PHP Crawling Client
---------
- Morphology
- Cron friendly (linux)
- Console/web interfaces
- Highload (memory-leaking wall)
- VM/screen friendly
- OOP


Structure
---------

```
common/                  defalt yii2 files  
console/                 defalt yii2 files  
    controllers/         console controller class (declaring and implementing methods to be used within console/terminal)
    
backend/                 defalt yii2 advanced-template files
    controllers/         controller classes for web-interface
    models/              contains specific model (or helper) classes
            captcha/ 
            morph/       helper class to work with phpmorphy
            parser/      main crawling-logic classes & oop-interfaces  
                source/  directory for any new crawl-source interface implemetations (requires max 4 method impl.)
            search/      search helpers
            sync/        helper class to update aggregator (marketplace) db 
            synonym/     helper class to work with synonymizers
            
frontend/                defalt yii2 files
vendor/                  defalt yii2 files
environments/            defalt yii2 files
```
