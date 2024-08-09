(function() {
  var template = Handlebars.template, templates = OCA.CustomGroups.Templates = OCA.CustomGroups.Templates || {};
templates['membersInput'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div>\n	<input class=\"member-input-field\" type=\"text\" placeholder=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"placeholderText") || (depth0 != null ? lookupProperty(depth0,"placeholderText") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"placeholderText","hash":{},"data":data,"loc":{"start":{"line":2,"column":60},"end":{"line":2,"column":79}}}) : helper)))
    + "\" />\n	<span class=\"loading icon-loading-small hidden\"></span>\n</div>\n\n";
},"useData":true});
})();
