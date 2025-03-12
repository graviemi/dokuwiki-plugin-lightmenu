# Lightmenu DokuWiki plugin

Very simple plugin which display pure CSS tree menu (inspired from "bisserof" https://codepen.io/bisserof) of wiki hierarchy. Meant to be placed in the sidebar.txt file.

![screenshot](https://github.com/graviemi/dokuwiki-plugin-lightmenu/blob/main/screenshot.png?raw=true)

**features** :
- full wiki tree access menu
- menu label customization name and style.


## Customize menu label

By Default the menu label is the dokuwiki id of the page or namespace (directory). You can change this label and style it thos way.

- Go to the page and edit it.
- Anywhere on the page type (you can put it on top nothing will be displayed except errors) :
```
<lm:{
	"label":"possible new label",
	"class":"possible CSS class",
	"style":"possible CSS code"
}>
```
- save
- the new label appear in menu for this page / namespace

**Important** : avoid ">" character inside code use the HTML entity "`&gt;`" instead since ">" mark the end of lightmenu code.
