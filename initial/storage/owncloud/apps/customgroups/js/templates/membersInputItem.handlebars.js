(function() {
  var template = Handlebars.template, templates = OCA.CustomGroups.Templates = OCA.CustomGroups.Templates || {};
templates['membersInputItem'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<li class=\"user\">\n	<a>\n		<div class=\"customgroups-autocomplete-item\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"userId") || (depth0 != null ? lookupProperty(depth0,"userId") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"userId","hash":{},"data":data,"loc":{"start":{"line":3,"column":53},"end":{"line":3,"column":63}}}) : helper)))
    + "\">\n			<div class='avatardiv'></div>\n			<div class='autocomplete-item-text'>"
    + alias4(((helper = (helper = lookupProperty(helpers,"displayName") || (depth0 != null ? lookupProperty(depth0,"displayName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"displayName","hash":{},"data":data,"loc":{"start":{"line":5,"column":39},"end":{"line":5,"column":54}}}) : helper)))
    + "</div>\n		</div>\n	</a>\n</li>\n";
},"useData":true});
})();
