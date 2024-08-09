<?php
/**
 * adfs2fed.php
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @copyright (C) 2017-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

if (\count($argv) < 3) {
	echo "Usage: php adfs2fed.php <metadata url or file> <scope>\n";
	echo "   eg. php adfs2fed.php https://sts.company.tld/FederationMetadata/2007-06/FederationMetadata.xml company.tld\n";
	echo "   or for a local metadata file: php adfs2fed.php FederationMetadata.xml company.tld\n";
	die(1);
}

$doc = new DOMDocument();
$doc->load($argv[1]);

$scope = $argv[2];

# remove signature, RoleDescriptor and SPSSODescriptor elements in order to
# avoid incompatibilities with the Switch WAYF shipped parser
$toDelete = [];
foreach (['Signature', 'RoleDescriptor', 'SPSSODescriptor'] as $tag) {
	foreach ($doc->getElementsByTagName($tag) as $el) {
		$toDelete[] = $el;
	}
}
foreach ($toDelete as $node) {
	$doc->documentElement->removeChild($node);
}

$xml = $doc->saveXML();

# add scope namespace and extension
$shibNameSpace = 'xmlns:shibmd="urn:mace:shibboleth:metadata:1.0"';
$xml = \str_replace(
	'xmlns="urn:oasis:names:tc:SAML:2.0:metadata"',
	'xmlns="urn:oasis:names:tc:SAML:2.0:metadata" '.$shibNameSpace,
	$xml
);
$extensions = "<Extensions>\n    <shibmd:Scope regexp=\"false\">$scope</shibmd:Scope>\n  </Extensions>\n  ";
$xml = \str_replace('<IDPSSODescriptor', $extensions . '<IDPSSODescriptor', $xml);

echo $xml;
