# Lightmenu DokuWiki plugin

Very simple plugin which display pure CSS tree menu (inspired from "bisserof" https://codepen.io/bisserof) of wiki hierarchy. Meant to be placed in the sidebar.txt file.

![screenshot](https://github.com/graviemi/dokuwiki-plugin-lightmenu/blob/main/screenshot.png?raw=true)

**features** :
- full wiki tree access menu
- menu label customization name and style.


## Customize menu label

By Default the menu label is the dokuwiki id of the page or namespace (directory). You can change this label and style it this way.
For style you have to learn a bit of CSS (Cascading Style Sheet)

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

### examples

- To change the current page label in menu to "New label" add : `<lm:{"label":"New label"}>`
- To change the color of the current page label in menu to red add : `<lm:{"style":"color:red;"}>`
- Change color and label add : `<lm:{"label":"New label","style":"color:red;"}>`
- Add CSS class to the new label add : `<lm:{"label":"New label","class":"my-classs-name"}>`

You can define CSS classes creating "conf/userstyle.css" file in dokuwiki tree.

### How does it works

The code after "<lm:" is JSON code.
- The content of "label" property is placed as text and "title" attribute of HTML link.
- The content of "class" property is placed in "class" attribute of HTML link. 
- The content of "style" property is placed in "style" attribute of HTML link.

HTML code injection is possible and not controlled by the plugin : hack if you want.
Incomming versions may authorize to add any HTML attribute to the link.

