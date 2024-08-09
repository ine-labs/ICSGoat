(function() {
  var template = Handlebars.template, templates = OCA.CustomGroups.Templates = OCA.CustomGroups.Templates || {};
templates['listItem'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "			<a href=\"#\" class=\"action-rename-group icon icon-rename\" title=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"renameLabel") || (depth0 != null ? lookupProperty(depth0,"renameLabel") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"renameLabel","hash":{},"data":data,"loc":{"start":{"line":7,"column":67},"end":{"line":7,"column":82}}}) : helper)))
    + "\"></a>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<a href=\"#\" class=\"action-delete-group icon icon-delete\" title=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"deleteLabel") || (depth0 != null ? lookupProperty(depth0,"deleteLabel") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"deleteLabel","hash":{},"data":data,"loc":{"start":{"line":12,"column":85},"end":{"line":12,"column":100}}}) : helper)))
    + "\"></a>";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<tr class=\"group select\" data-id=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":34},"end":{"line":1,"column":40}}}) : helper)))
    + "\">\n	<td class=\"avatar-column\"><div class=\"avatar\"></div></td>\n	<td class=\"display-name\">\n		<span class=\"display-name-container\">\n			<span class=\"group-display-name\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"displayName") || (depth0 != null ? lookupProperty(depth0,"displayName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"displayName","hash":{},"data":data,"loc":{"start":{"line":5,"column":36},"end":{"line":5,"column":51}}}) : helper)))
    + "</span>\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"canAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":6,"column":3},"end":{"line":8,"column":10}}})) != null ? stack1 : "")
    + "		</span>\n	</td>\n	<td class=\"role-display-name\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"roleDisplayName") || (depth0 != null ? lookupProperty(depth0,"roleDisplayName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"roleDisplayName","hash":{},"data":data,"loc":{"start":{"line":11,"column":31},"end":{"line":11,"column":50}}}) : helper)))
    + "</td>\n	<td>"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"canAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":12,"column":5},"end":{"line":12,"column":113}}})) != null ? stack1 : "")
    + "</td>\n</tr>\n";
},"useData":true});
})();
