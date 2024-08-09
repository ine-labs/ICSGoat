(function() {
  var template = Handlebars.template, templates = OCA.CustomGroups.Templates = OCA.CustomGroups.Templates || {};
templates['membersList'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"header\">\n</div>\n<table class=\"grid hidden\">\n	<thead>\n		<th></th>\n		<th>"
    + alias4(((helper = (helper = lookupProperty(helpers,"memberLabelHeader") || (depth0 != null ? lookupProperty(depth0,"memberLabelHeader") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"memberLabelHeader","hash":{},"data":data,"loc":{"start":{"line":6,"column":6},"end":{"line":6,"column":27}}}) : helper)))
    + "</th>\n		<th>"
    + alias4(((helper = (helper = lookupProperty(helpers,"roleLabelHeader") || (depth0 != null ? lookupProperty(depth0,"roleLabelHeader") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"roleLabelHeader","hash":{},"data":data,"loc":{"start":{"line":7,"column":6},"end":{"line":7,"column":25}}}) : helper)))
    + "</th>\n		<th></th>\n	</thead>\n	<tbody class=\"group-member-list\">\n	</tbody>\n</table>\n<div class=\"loading loading-list\" style=\"height: 50px\"></div>\n";
},"useData":true});
})();
