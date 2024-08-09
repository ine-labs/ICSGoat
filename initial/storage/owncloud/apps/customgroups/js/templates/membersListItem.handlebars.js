(function() {
  var template = Handlebars.template, templates = OCA.CustomGroups.Templates = OCA.CustomGroups.Templates || {};
templates['membersListItem'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "	<a href=\"#\" class=\"action-change-member-role icon icon-rename\" title=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"changeMemberRoleLabel") || (depth0 != null ? lookupProperty(depth0,"changeMemberRoleLabel") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"changeMemberRoleLabel","hash":{},"data":data,"loc":{"start":{"line":6,"column":71},"end":{"line":6,"column":96}}}) : helper)))
    + "\"></a>\n	<span class=\"loading icon-loading-small hidden\"></span>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<a href=\"#\" class=\"action action-delete-member\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"deleteLabel") || (depth0 != null ? lookupProperty(depth0,"deleteLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deleteLabel","hash":{},"data":data,"loc":{"start":{"line":10,"column":76},"end":{"line":10,"column":91}}}) : helper)))
    + "\"><span class=\"icon icon-delete\"></span><span class=\"hidden-visually\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"deleteMemberLabel") || (depth0 != null ? lookupProperty(depth0,"deleteMemberLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deleteMemberLabel","hash":{},"data":data,"loc":{"start":{"line":10,"column":161},"end":{"line":10,"column":182}}}) : helper)))
    + "</span></a>";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<tr class=\"group-member\" data-id=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":34},"end":{"line":1,"column":40}}}) : helper)))
    + "\">\n	<td class=\"avatar-column\"><div class=\"avatar\"></div></td>\n	<td class=\"user-display-name\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"displayName") || (depth0 != null ? lookupProperty(depth0,"displayName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"displayName","hash":{},"data":data,"loc":{"start":{"line":3,"column":38},"end":{"line":3,"column":53}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"displayName") || (depth0 != null ? lookupProperty(depth0,"displayName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"displayName","hash":{},"data":data,"loc":{"start":{"line":3,"column":55},"end":{"line":3,"column":70}}}) : helper)))
    + "</td>\n	<td><span class=\"role-display-name\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"roleDisplayName") || (depth0 != null ? lookupProperty(depth0,"roleDisplayName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"roleDisplayName","hash":{},"data":data,"loc":{"start":{"line":4,"column":37},"end":{"line":4,"column":56}}}) : helper)))
    + "</span>\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"canAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":1},"end":{"line":8,"column":8}}})) != null ? stack1 : "")
    + "	</td>\n	<td>"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"canAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":10,"column":5},"end":{"line":10,"column":200}}})) != null ? stack1 : "")
    + "</td>\n</tr>\n";
},"useData":true});
})();
