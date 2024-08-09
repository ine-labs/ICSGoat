(function() {
  var template = Handlebars.template, templates = OCA.CustomGroups.Templates = OCA.CustomGroups.Templates || {};
templates['list'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<form name=\"customGroupsCreationForm\">\n	<div>\n		<input type=\"text\" name=\"groupName\" placeholder=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"newGroupPlaceholder") || (depth0 != null ? lookupProperty(depth0,"newGroupPlaceholder") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newGroupPlaceholder","hash":{},"data":data,"loc":{"start":{"line":4,"column":51},"end":{"line":4,"column":74}}}) : helper)))
    + "\" autocomplete=\"off\" autocapitalize=\"off\" autocorrect=\"off\">\n		<input type=\"submit\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"newGroupSubmitLabel") || (depth0 != null ? lookupProperty(depth0,"newGroupSubmitLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newGroupSubmitLabel","hash":{},"data":data,"loc":{"start":{"line":5,"column":30},"end":{"line":5,"column":53}}}) : helper)))
    + "\" />\n	</div>\n</form>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = "";

  stack1 = ((helper = (helper = lookupProperty(helpers,"canCreate") || (depth0 != null ? lookupProperty(depth0,"canCreate") : depth0)) != null ? helper : alias2),(options={"name":"canCreate","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":8,"column":14}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"canCreate")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "<form name=\"customGroupsRenameForm\" class=\"hidden group-rename-form\">\n	<input type=\"text\" value=\"\" placeholder=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"newGroupPlaceholder") || (depth0 != null ? lookupProperty(depth0,"newGroupPlaceholder") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newGroupPlaceholder","hash":{},"data":data,"loc":{"start":{"line":10,"column":42},"end":{"line":10,"column":65}}}) : helper)))
    + "\" />\n</form>\n<table class=\"grid hidden\">\n<thead>\n	<tr>\n		<th></th>\n		<th>"
    + alias4(((helper = (helper = lookupProperty(helpers,"groupLabel") || (depth0 != null ? lookupProperty(depth0,"groupLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"groupLabel","hash":{},"data":data,"loc":{"start":{"line":16,"column":6},"end":{"line":16,"column":20}}}) : helper)))
    + "</th>\n		<th>"
    + alias4(((helper = (helper = lookupProperty(helpers,"yourRoleLabel") || (depth0 != null ? lookupProperty(depth0,"yourRoleLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"yourRoleLabel","hash":{},"data":data,"loc":{"start":{"line":17,"column":6},"end":{"line":17,"column":23}}}) : helper)))
    + "</th>\n		<th></th>\n	</tr>\n</thead>\n<tbody class=\"group-list\">\n</tbody>\n</table>\n<div class=\"empty hidden\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"emptyMessage") || (depth0 != null ? lookupProperty(depth0,"emptyMessage") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"emptyMessage","hash":{},"data":data,"loc":{"start":{"line":24,"column":26},"end":{"line":24,"column":42}}}) : helper)))
    + "</div>\n<div class=\"loading hidden\" style=\"height: 50px\"></div>\n";
},"useData":true});
})();
