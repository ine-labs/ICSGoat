/**
 * ownCloud Wopi
 *
 * @author Piotr Mrowczynski <piotr@owncloud.com>
 * @copyright 2019 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
(function ($, OC, OCA) {

	/**
	 * Default WOPI locale id
	 *
	 * https://wopi.readthedocs.io/en/latest/discovery.html#term-ui-llcc
	 *
	 * @type {string}
	 */
	var defaultLocaleId = "en-US";

	/**
	 * Supported WOPI locale id list
	 *
	 * https://wopi.readthedocs.io/en/latest/faq/languages.html#languages
	 *
	 * @type {array}
	 */
	var wopiLocaleIds = {
		"af": "af-ZA", // af-ZA	Afrikaans	1078
		"am": "am-ET", // am-ET	Amharic	1118
		"ar": "ar-SA", // ar-SA	Arabic	1025
		"as": "as-IN", // as-IN	Assamese	1101
		"az": "az-Latn-AZ", // az-Latn-AZ	Azerbaijani (Latin)	1068
		"be": "be-BY", // be-BY	Belarusian	1059
		"bg": "bg-BG", // bg-BG	Bulgarian	1026
		"bn": "bn-BD", // bn-BD	Bangla (Bangladesh)	2117
		"bs": "bs-Latn-BA", // bs-Latn-BA	Bosnian (Latin)	5146
		"ca": "ca-ES", // ca-ES	Catalan	1027
		"chr": "chr-Cher-US", // chr-Cher-US	Cherokee	1116
		"cs": "cs-CZ", // cs-CZ	Czech	1029
		"cy": "cy-GB", // cy-GB	Welsh	1106
		"da": "da-DK", // da-DK	Danish	1030
		"de": "de-DE", // de-DE	German	1031
		"el": "el-GR", // el-GR	Greek	1032
		"en": "en-US", // en-US	English (United States)	1033
		"es": "es-ES", // es-ES	Spanish	3082
		"et": "et-EE", // et-EE	Estonian	1061
		"eu": "eu-ES", // eu-ES	Basque	1069
		"fa": "fa-IR", // fa-IR	Persian (aka Farsi)	1065
		"fi": "fi-FI", // fi-FI	Finnish	1035
		"fil": "fil-PH", // fil-PH	Filipino	1124
		"fr": "fr-FR", // fr-FR	French	1036
		"ga": "ga-IE", // ga-IE	Gaelic Irish	2108
		"gd": "gd-GB", // gd-GB	Scottish Gaelic	1084
		"gl": "gl-ES", // gl-ES	Galician	1110
		"gu": "gu-IN", // gu-IN	Gujarati	1095
		"ha": "ha-Latn-NG", // ha-Latn-NG	Hausa (Latin)	1128
		"he": "he-IL", // he-IL	Hebrew	1037
		"hi": "hi-IN", // hi-IN	Hindi	1081
		"hr": "hr-HR", // hr-HR	Croatian	1050
		"hu": "hu-HU", // hu-HU	Hungarian	1038
		"hy": "hy-AM", // hy-AM	Armenian	1067
		"id": "id-ID", // id-ID	Indonesian	1057
		"is": "is-IS", // is-IS	Icelandic	1039
		"it": "it-IT", // it-IT	Italian	1040
		"ja": "ja-JP", // ja-JP	Japanese	1041
		"ka": "ka-GE", // ka-GE	Georgian	1079
		"kk": "kk-KZ", // kk-KZ	Kazakh	1087
		"km": "km-KH", // km-KH	Khmer	1107
		"kn": "kn-IN", // kn-IN	Kannada	1099
		"kok": "kok-IN", // kok-IN	Konkani	1111
		"ko": "ko-KR", // ko-KR	Korean	1042
		"ky": "ky-KG", // ky-KG	Kyrgyz	1088
		"lb": "lb-LU", // lb-LU	Luxembourgish	1134
		"lo": "lo-la", // lo-la	Lao	1108
		"lt": "lt-LT", // lt-LT	Lithuanian	1063
		"lv": "lv-LV", // lv-LV	Latvian	1062
		"mi": "mi-NZ", // mi-NZ	Maori	1153
		"mk": "mk-MK", // mk-MK	Macedonian	1071
		"ml": "ml-IN", // ml-IN	Malayalam	1100
		"mn": "mn-MN", // mn-MN	Mongolian (Cyrillic)	1104
		"mr": "mr-IN", // mr-IN	Marathi	1102
		"ms": "ms-MY", // ms-MY	Malay	1086
		"mt": "mt-MT", // mt-MT	Maltese	1082
		"nb": "nb-NO", // nb-NO	Norwegian (Bokmål)	1044
		"ne": "ne-NP", // ne-NP	Nepali	1121
		"nl": "nl-NL", // nl-NL	Dutch	1043
		"nn": "nn-NO", // nn-NO	Norwegian (Nynorsk)	2068
		"or": "or-IN", // or-IN	Odia (aka Oriya)	1096
		"pa": "pa-IN", // pa-IN	Punjabi (India)	1094
		"pl": "pl-PL", // pl-PL	Polish	1045
		"prs": "prs-AF", // prs-AF	Dari	1164
		"pt": "pt-PT", // pt-PT	Portuguese (Portugal)	2070
		"quz": "quz-PE", // quz-PE	Quechua	3179
		"ro": "ro-Ro", // ro-Ro	Romanian	1048
		"ru": "ru-Ru", // ru-Ru	Russian	1049
		"sd": "sd-Arab-PK", // sd-Arab-PK	Sindhi	2137
		"si": "si-LK", // si-LK	Sinhala	1115
		"sk": "sk-SK", // sk-SK	Slovak	1051
		"sl": "sl-SI", // sl-SI	Slovenian	1060
		"sq": "sq-AL", // sq-AL	Albanian	1052
		"sr": "sr-Cyrl-RS", // sr-Cyrl-RS	Serbian (Cyrillic)	10266
		"sv": "sv-SE", // sv-SE	Swedish	1053
		"sw": "sw-KE", // sw-KE	Kiswahili	1089
		"ta": "ta-IN", // ta-IN	Tamil	1097
		"te": "te-IN", // te-IN	Telugu	1098
		"th": "th-TH", // th-TH	Thai	1054
		"tk": "tk-TM", // tk-TM	Turkmen	1090
		"tr": "tr-TR", // tr-TR	Turkish	1055
		"tt": "tt-RU", // tt-RU	Tatar	1092
		"ug": "ug-CN", // ug-CN	Uyghur	1152
		"uk": "uk-UA", // uk-UA	Ukrainian	1058
		"ur": "ur-PK", // ur-PK	Urdu	1056
		"uz": "uz-Latn-UZ", // uz-Latn-UZ	Uzbek	1091
		"vi": "vi-VN", // vi-VN	Vietnamese	1066
		"zh": "zh-CN" // zh-CN	Chinese (Simplified)	2052
	};

	OCA.WopiLang = {

		getLocale: function () {
			var locale = OC.getLocale().split("_")[0];
			if (locale in wopiLocaleIds) {
				return wopiLocaleIds[locale];
			}
			return defaultLocaleId;
		}
	};

})(jQuery, OC, OCA);
