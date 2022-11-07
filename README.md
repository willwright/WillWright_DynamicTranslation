# WillWright_DynamicTranslation

Magento 2 module that allows a developer to flag rows in translation dictionaries to be added regardless of whether a
instance of the string is matched or not.

## Install
`composer require will-wright/module-dynamic-translation`

## Usage
Append `,dynamic` to any translation which should be added to the dictionary (`js-translation.json`).

### Example
`en_US.csv`
```
"value1","translated value1",dynamic
"value2","translated value2",dynamic
"value3","value3",dynamic
```

Results in the following `js-translation.json` response
```json
{"value1":"translated value1","value2":"translated value2","value3":"value3"}
```

## Scenario
This is helpful if a developer has a value which needs to be translated but the value is part of a dynamic output in a
knockout template.

`Willwright/Demo/view/frontend/web/template/demo.html`
```html
<ul data-bind="foreach: getValues()">
    <li>
        <span data-bind="i18n: $data"></span>
    </li>
</ul>
```

`Willwright/Demo/view/frontend/web/js/demo.js`
```js
define(['jquery', 'uiComponent', 'ko'], function ($, Component, ko) {
        'use strict';
        return Component.extend({
            initialize: function () {
                this._super();
            },
            getValues: function(){
                return [
                    'value1',
                    'value2',
                    'value3'
                ];
            }
        });
    }
);
```

Normally the above values would **not** be translated. However, with this module and `,dynamic` added to the appropriate
rows the values **will** be translated.
