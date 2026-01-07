/**
 * Resources
 * https://github.com/InteractiveAdvertisingBureau/iabtcf-es
 * */
import {CmpApi} from '@iabtechlabtcf/cmpapi';
import {GVL, TCModel, TCString} from '@iabtechlabtcf/core';

const cmplzCMP = 332;
const cmplzCMPVersion = 1;
const cmplzIsServiceSpecific = cmplz_tcf.isServiceSpecific == 1 ? true : false;
const cmplzExistingLanguages = ['gl','eu','bg', 'ca', 'cs', 'da', 'de', 'el', 'es', 'et', 'fi', 'fr', 'hr', 'hu', 'it', 'ja', 'lt', 'lv', 'mt', 'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'tr', 'zh',];
let langCount = cmplzExistingLanguages.length;
let cmplz_html_lang_attr =  document.documentElement.lang.length ? document.documentElement.lang.toLowerCase() : 'en';
let cmplzLanguage = 'en';
for (let i = 0; i < langCount; i++) {
	let cmplzLocale = cmplzExistingLanguages[i];
	//nb_no should be matched on no, not nb
	if ( cmplz_html_lang_attr==='nb-no' ) {
		cmplzLanguage = 'no';
		break;
	}
	//needs to be exact match, as for example "ca" (catalan) occurs in "fr-ca", which should only match on "fr"
	if ( cmplz_html_lang_attr.indexOf(cmplzLocale)===0 ) {
		cmplzLanguage = cmplzLocale;
		break;
	}
}
if (cmplzLanguage==='eu') cmplzLanguage='eus';
let cmplzLanguageJson;
let dataCategories = [];
let ACVendors = [];
let useAcVendors = cmplz_tcf.ac_mode;

let onOptOutPolicyPage = document.getElementById('cmplz-tcf-us-vendor-container') !== null;
let onOptInPolicyPage = document.getElementById('cmplz-tcf-vendor-container') !== null;
/**
 * initialize the __tcfapi function and post message
 * https://github.com/InteractiveAdvertisingBureau/iabtcf-es/tree/master/modules/stub
 */

let ACVendorsUrl = cmplz_tcf.cmp_url + 'cmp/vendorlist/additional-consent-providers.csv';
let purposesUrl = cmplz_tcf.cmp_url+'cmp/vendorlist'+'/purposes-'+cmplzLanguage+'.json';
if (!cmplzExistingLanguages.includes(cmplzLanguage)) {
	cmplzLanguage = 'en';
	purposesUrl = cmplz_tcf.cmp_url + 'cmp/vendorlist' + '/vendor-list.json';
}

/**
 * Get a cookie by name
 * @param name
 * @returns {string}
 */

function cmplz_tcf_get_cookie(name) {
	if ( typeof document === "undefined" ) {
		return "";
	}
	let prefix = typeof complianz !== "undefined" ? complianz.prefix : 'cmplz_';
	const value = "; " + document.cookie;
	const parts = value.split("; " + prefix + name + "=");
	if ( parts.length === 2 ) {
		return parts.pop().split(";").shift();
	}
	return "";
}

/**
 * Add an event
 * @param event
 * @param selector
 * @param callback
 * @param context
 */
function cmplz_tcf_add_event(event, selector, callback, context) {
	document.addEventListener(event, e => {
		if ( e.target.closest(selector) ) {
			callback(e);
		}
	});
}

/**
 * Check if the element is hidden
 * @param el
 * @returns {boolean}
 */
function is_hidden(el) {
	return (el.offsetParent === null)
}

let bannerDataLoadedResolve;
let tcModelLoadedResolve;
let tcfLanguageLoadedResolve;
let bannerLoadedResolve;
let revokeResolve;
let bannerDataLoaded = new Promise(function(resolve, reject){
	bannerDataLoadedResolve = resolve;
});
let tcModelLoaded = new Promise(function(resolve, reject){
	tcModelLoadedResolve = resolve;
});
let tcfLanguageLoaded = new Promise(function(resolve, reject){
	tcfLanguageLoadedResolve = resolve;
});
let bannerLoaded = new Promise(function(resolve, reject){
	bannerLoadedResolve = resolve;
});
let revoke = new Promise(function(resolve, reject){
	revokeResolve = resolve;
});

const acVendorsPromise = useAcVendors ? fetch(ACVendorsUrl)
	.then(response => response.text())
	.then(csvData => {
		// Parse the CSV data
		const rows = csvData.split('\n');
		// Remove first row
		rows.shift();
		// Convert array of arrays to array of objects
		ACVendors = rows.map(row => {
			if (row.length === 0) {
				return null;
			}
			const [id, name, policyUrl, domains] = cmplzParseCsvRow(row);
			return {
				id: parseInt(id),
				name,
				policyUrl,
				domains,
				consent: 0, // Default no consent
			};
		});
		// Filter out null values
		ACVendors = ACVendors.filter(el => el != null);
	})
	.catch(error => {
		console.log('Error:', error);
	}) : Promise.resolve();

const purposesPromise = fetch(purposesUrl, {
	method: "GET",
})
	.then(response => response.json())
	.then(data => {
		cmplzLanguageJson = data;
	})
	.catch(error => {
		console.log('Error:', error);
	});

Promise.all([acVendorsPromise, purposesPromise]).then(() => {
	tcfLanguageLoadedResolve();
});

document.addEventListener('wp_consent_type_defined', function (e) {
	bannerDataLoadedResolve();
});
document.addEventListener('cmplz_cookie_warning_loaded', function (e) {
	if ( !complianz.disable_cookiebanner) {
		bannerLoadedResolve();
	}
});
document.addEventListener('cmplz_revoke', function (e) {
	const reload = e.detail;
	revokeResolve(reload);
});

bannerDataLoaded.then(()=>{

});

tcfLanguageLoaded.then(()=>{
	const storedTCString = cmplzGetTCString();
	const ACString = cmplzGetACString();
	GVL.baseUrl = cmplz_tcf.cmp_url + "cmp/vendorlist";
	dataCategories = cmplzLanguageJson.dataCategories;

	let gvl = new GVL(cmplzLanguageJson);

	let sourceGvl = null;

	// Resolving changeLanguage promise.
	gvl.changeLanguage(cmplzLanguage).then(() => {
		return gvl.readyPromise;
	}).then(() => {
		sourceGvl = gvl.clone();
	});

	let tcModel = new TCModel(gvl);
	tcModel.publisherCountryCode = cmplz_tcf.publisherCountryCode;
	tcModel.version = 2;
	tcModel.cmpId = cmplzCMP;
	tcModel.cmpVersion = cmplzCMPVersion;
	tcModel.isServiceSpecific = cmplzIsServiceSpecific;
	tcModel.UseNonStandardStacks = 0; //A CMP that services multiple publishers sets this value to 0
	const cmpApi = new CmpApi(cmplzCMP, cmplzCMPVersion, cmplzIsServiceSpecific, {
		//https://github.com/InteractiveAdvertisingBureau/iabtcf-es/tree/master/modules/cmpapi#built-in-and-custom-commands
		'getTCData': (next, tcData, success) => {
			// tcData will be constructed via the TC string and can be added to here
			//to prevent the removeEventListener action to return null instead of true, we need the check if the tcData is an object, and if the ACString exists.
			if ( tcData && ACString && typeof tcData === 'object' ) {
				tcData.addtlConsent = ACString;
				tcData.enableAdvertiserConsentMode = !(ACVendors.length === 0 || typeof ACVendors[0].consent === 'undefined');
			}
			// pass data along
			next(tcData, success);
		}
	});


	/**
	 * After banner data is fully loaded
	 */

	tcModel.gvl.readyPromise.then(() => {
		const json = tcModel.gvl.getJson();
		let vendors = json.vendors;
		let vendorIds = cmplzFilterVendors(vendors);
		tcModel.gvl.narrowVendorsTo(vendorIds);

		//update model with given consents
		try {
			tcModel = TCString.decode(storedTCString, tcModel);

			//update tcmodel to ensure gdpr applies is set
			cmplzSetTCString(tcModel, cmplzUIVisible() );
			ACVendors = updateACVendorsWithConsent(ACString, ACVendors);
		} catch (err) {}

		//get the given consents from the Google Extended vendors
		tcModelLoadedResolve();
	});

	Promise.all([bannerDataLoaded, tcModelLoaded]).then(()=> {
		insertVendorsInPolicy(tcModel.gvl.vendors, ACVendors);
		if (complianz.consenttype === 'optin'){
			if (cmplz_tcf.debug) console.log(tcModel);
			let date = new Date();
			/**
			 * If the TC String was created over a year ago, we clear it.
			 */
			if (Date.parse(tcModel.created) < date.getTime() - 365 * 24 * 60 * 60 * 1000) {
				cmplzSetTCString(null, cmplzUIVisible() );
			} else {
				cmplzSetTCString(tcModel, cmplzUIVisible() );
			}
		} else {
			if (cmplz_tcf.debug) console.log("not an optin tcf region");
			cmplzSetTCString(null, false );
		}
	});

	Promise.all([bannerLoaded, tcModelLoaded, tcfLanguageLoaded]).then(()=> {
		configureOptinBanner();
	});

	revoke.then(reload => {
		if (cmplz_is_tcf_region(complianz.region)) {
			revokeAllVendors(reload);
		}
	});

	/**
	 * When the marketing is accepted, make sure all vendors are allowed
	 */

	document.addEventListener("cmplz_fire_categories", function (e) {
		//skip if not gdpr
		if (complianz.consenttype !== 'optin') {
			return;
		}
		if (cmplz_in_array('marketing', e.detail.categories)) {
			acceptAllVendors();
		}
	});

	/**
	 * Accept all vendors
	 */
	function acceptAllVendors() {
		consentAllACVendors();

		cmplzSetAllVendorLegitimateInterests();
		tcModel.setAllPurposeLegitimateInterests();
		for (let key in cmplz_tcf.purposes) {
			tcModel.purposeConsents.set(cmplz_tcf.purposes[key]);
			cmplzSetTypeByVendor('purpose_legitimate_interest', cmplz_tcf.purposes[key]);
		}

		tcModel.setAllSpecialFeatureOptins()
		for (let key in cmplz_tcf.specialFeatures) {
			tcModel.specialFeatureOptins.set(cmplz_tcf.specialFeatures[key]);
			cmplzSetTypeByVendor('specialfeature', cmplz_tcf.specialFeatures[key]);
		}

		tcModel.setAllPurposeConsents();
		for (let key in cmplz_tcf.purposes) {
			tcModel.purposeConsents.set(cmplz_tcf.purposes[key]);
			cmplzSetTypeByVendor('purpose_consent', cmplz_tcf.purposes[key]);
		}

		tcModel.setAllVendorConsents();
		document.querySelectorAll('.cmplz-tcf-input').forEach(checkbox => {
			checkbox.checked = true;
		});
		cmplzSetTCString(tcModel, cmplzUIVisible() );
		cmplz_set_cookie('banner-status', 'dismissed');
	}

	/**
	 * Revoke all vendors
	 * @param reload
	 */
	function revokeAllVendors(reload) {
		denyAllACVendors();

		//legint should be handled by right to object checkbox in vendor overview.
		// tcModel.unsetAllVendorLegitimateInterests();
		tcModel.unsetAllPurposeLegitimateInterests();
		cmplzUnsetAllVendorLegitimateInterests();
		for ( let key in cmplz_tcf.specialFeatures ) {
			tcModel.specialFeatureOptins.set(cmplz_tcf.specialFeatures[key]);
			cmplzUnsetTypeByVendor('specialfeature', cmplz_tcf.specialFeatures[key]);
		}

		for (let key in cmplz_tcf.purposes) {
			tcModel.purposeConsents.set(cmplz_tcf.purposes[key]);
			cmplzUnsetTypeByVendor('purpose_consent', cmplz_tcf.purposes[key]);
		}

		tcModel.unsetAllVendorConsents();
		document.querySelectorAll('.cmplz-tcf-input').forEach(checkbox => {
			if (!checkbox.disabled) checkbox.checked = false;
		});
		cmplzSetTCString(tcModel, cmplzUIVisible() );

		if (reload) {
			location.reload();
		}
	}

	/**
	 * Set all legitimate interests, except when a vendor does not have legints or special purposes.
	 */
	function cmplzSetAllVendorLegitimateInterests() {
		tcModel.setAllVendorLegitimateInterests();
		for (let key in tcModel.gvl.vendors) {
			let vendor = tcModel.gvl.vendors[key];
			/**
			 * no legint, and no special purposes, set legint signal to 0.
			 */
			if ( vendor.legIntPurposes.length === 0 && vendor.specialPurposes.length === 0 ) {
				tcModel.vendorLegitimateInterests.unset(vendor.id);
			}
		}
	}

	/**
	 * UnSet all legitimate interests, except when a vendor does not have legints or special purposes.
	 */
	function cmplzUnsetAllVendorLegitimateInterests() {
		tcModel.unsetAllVendorLegitimateInterests();
		for (let key in tcModel.gvl.vendors) {
			let vendor = tcModel.gvl.vendors[key];
			/**
			 * If a vendor only has special purposes, and no other purposes, there's no right to object.
			 */
			if (vendor.legIntPurposes.length === 0 && vendor.purposes.length === 0 && vendor.flexiblePurposes.length === 0 && vendor.specialFeatures.length === 0 && vendor.specialPurposes.length !== 0) {
				tcModel.vendorLegitimateInterests.set(vendor.id);
			}
		}
	}

	const updateACVendorsWithConsent = (storedConsentString, vendors) => {
		//retrieve array of consented vendors from the stored consent string
		let consentedVendors = decodeACString(storedConsentString);
		//loop through ACVendors, and set each vendor that exists in the consentedVendor array to consented
		vendors.forEach((vendor) => {
			if (consentedVendors.includes(vendor.id)) {
				vendor.consent = 1;
			}
		})
		//strip out vendors that already exist in the tcf vendor list
		const tcfVendorNames = new Set(Object.values(tcModel.gvl.vendors).map( vendor => vendor.name ));
		return vendors.filter(vendor => !tcfVendorNames.has(vendor.name));
	}

	const denyACVendor = (vendorId) => {
		ACVendors.forEach((vendor) => {
			if (parseInt(vendor.id) === vendorId) {
				vendor.consent = 0;
			}
		});
	}
	const denyAllACVendors = () => {
		ACVendors.forEach((vendor) => {
			vendor.consent = 0;
		})
	}
	const consentAllACVendors = () => {
		ACVendors.forEach((vendor) => {
			vendor.consent = 1;
		})
	}
	const consentACVendor = (vendorId) => {
		ACVendors.forEach((vendor) => {
			if (parseInt(vendor.id) === vendorId) {
				vendor.consent = 1;
			}
		});
	}

	const decodeACString = (ACString) => {
		if (!ACString || ACString.length===0) return [];

		//split the string on the ~
		let ACArray = ACString.split('~');
		//get the version number
		let ACversion = ACArray[0];
		//get the array of vendor id's
		let vendors = ACArray[1].split('.');
		// change each vendor id to an integer
		return vendors.map((vendor) => {
			return parseInt(vendor);
		})
	}

	/**
	 * If a purpose has been selected/deselected, we need to re-check for al vendors if this has consenquences for legint
	 */
	function cmplzUpdateAllVendorLegitimateInterests() {
		for (let key in tcModel.gvl.vendors) {
			let vendor = tcModel.gvl.vendors[key];

			/**
			 * no legint, and no special purposes, set legint signal to 0.
			 */
			if (vendor.legIntPurposes.length === 0 && vendor.specialPurposes.length === 0) {
				tcModel.vendorLegitimateInterests.unset(vendor.id);
			}

			if (vendor.legIntPurposes.length === 0 && vendor.purposes.length === 0 && vendor.flexiblePurposes.length === 0 && vendor.specialFeatures.length === 0 && vendor.specialPurposes.length !== 0) {
				tcModel.vendorLegitimateInterests.set(vendor.id);
			}
		}
	}

	/**
	 * We use this method to keep track of consents per vendor. This is not stored in the core tcString
	 *
	 * @param type
	 * @param typeId
	 */
	function cmplzSetTypeByVendor(type, typeId) {
		if (type === 'purpose_consent') {
			tcModel.purposeConsents.set(typeId);
			for (let key in tcModel.gvl.vendors) {
				let vendor = tcModel.gvl.vendors[key];
				if (sourceGvl.vendors[vendor.id].purposes.includes(typeId) && !vendor.purposes.includes(typeId)) {
					tcModel.gvl.vendors[vendor.id].purposes.push(typeId);
				}
			}
		}

		if (type === 'purpose_legitimate_interest') {
			tcModel.purposeLegitimateInterests.set(typeId);
			for (let key in tcModel.gvl.vendors) {
				let vendor = tcModel.gvl.vendors[key];
				if (sourceGvl.vendors[vendor.id].purposes.includes(typeId) && !vendor.purposes.includes(typeId)) {
					tcModel.gvl.vendors[vendor.id].purposes.push(typeId);
				}
			}
		}

		if (type === 'specialfeature') {
			tcModel.specialFeatureOptins.set(typeId);
			for (let key in tcModel.gvl.vendors) {
				let vendor = tcModel.gvl.vendors[key];
				if (sourceGvl.vendors[vendor.id].specialFeatures.includes(typeId) && !vendor.specialFeatures.includes(typeId)) {
					tcModel.gvl.vendors[vendor.id].specialFeatures.push(typeId);
				}
			}
		}

		if (type === 'feature') {
			for (let key in tcModel.gvl.vendors) {
				let vendor = tcModel.gvl.vendors[key];
				if (sourceGvl.vendors[vendor.id].features.includes(typeId) && !vendor.features.includes(typeId)) {
					tcModel.gvl.vendors[vendor.id].features.push(typeId);
				}
			}
		}
	}

	function cmplzUnsetTypeByVendor(type, typeId) {
		if (type === 'purpose_consent') {
			tcModel.purposeConsents.unset(typeId);
			for (let key in tcModel.gvl.vendors) {
				let vendor = tcModel.gvl.vendors[key];
				let index = vendor.purposes.indexOf(typeId);
				if (index > -1) {
					tcModel.gvl.vendors[vendor.id].purposes.splice(index, 1);
				}
			}
		}

		if (type === 'purpose_legitimate_interest') {
			tcModel.purposeLegitimateInterests.unset(typeId);
			for (let key in tcModel.gvl.vendors) {
				let vendor = tcModel.gvl.vendors[key];
				let index = vendor.legIntPurposes.indexOf(typeId);
				if (index > -1) {
					tcModel.gvl.vendors[vendor.id].legIntPurposes.splice(index, 1);
				}
			}
		}

		if (type === 'specialfeature') {
			tcModel.specialFeatureOptins.unset(typeId);
			for (let key in tcModel.gvl.vendors) {
				let vendor = tcModel.gvl.vendors[key];
				const index = vendor.specialFeatures.indexOf(typeId);
				if (index > -1) {
					tcModel.gvl.vendors[vendor.id].specialFeatures.splice(index, 1);
				}
			}
		}

		if (type === 'feature') {
			for (let key in tcModel.gvl.vendors) {
				let vendor = tcModel.gvl.vendors[key];
				const index = vendor.features.indexOf(typeId);
				if (index > -1) {
					tcModel.gvl.vendors[vendor.id].features.splice(index, 1);
				}
			}
		}
	}

	/**
	 * When revoke button is clicked, so banner shows again
	 *
	 */

	cmplz_tcf_add_event('click', '.cmplz-manage-consent', function () {
		const storedTCString = cmplzGetTCString();
		cmpApi.update(storedTCString, true);  //just got the banner to show again, so we have to pass ui visible true
	});

	/**
	 * Create a checkbox, clickable
	 * @param type
	 * @param object
	 * @param container
	 * @param checked
	 * @param disabled
	 */
	function cmplzRenderCheckbox(type, object, container, checked, disabled) {
		const { name, description, id, illustrations } = object;
		let illustration = illustrations && illustrations.hasOwnProperty(0) ? illustrations[0] : '';
		const vendors = tcModel.gvl.vendors;
		let vendorsWithPurpose = cmplzGetVendorsWithPurpose('purposes', vendors, [id]);
		const count = vendorsWithPurpose.length;

		const descArr = description.split('*');
		const descriptionOut = descArr.join(', ');
		const template = document.getElementById('cmplz-tcf-type-template').innerHTML
			.replace(/{type_name}/g, name )
			.replace(/{type_count}/g, count )
			.replace(/{type_description}/g, descriptionOut)
			.replace(/{type_id}/g, id)
			.replace(/{type_example}/g, illustration)
			.replace(/{type}/g, type);

		const wrapper = document.createElement('div');
		wrapper.innerHTML = template;
		const checkbox = wrapper.firstChild;
		const input = checkbox.querySelector(`.cmplz-tcf-${type}-input`);
		input.checked = checked;
		input.disabled = disabled;
		input.setAttribute(`data-${type}_id`, id);

		container.appendChild(checkbox);
	}

	/**
	 * Generate entire block of checkboxes with event listener
	 * @param type
	 * @param objects
	 * @param filterBy
	 */

	function generateTypeBlock(type, objects, filterBy) {
		let containerid = type;
		let srcPurposes;
		if (filterBy !== false) {
			containerid = filterBy + '-' + containerid;
			srcPurposes = getPurposes(filterBy, false);
		}

		const container = document.getElementById('cmplz-tcf-' + containerid + 's-container');
		if (container === null) {
			return;
		}
		container.innerHTML = '';
		for (let key in objects) {
			if (objects.hasOwnProperty(key)) {
				const object = objects[key];
				const addItem = filterBy ? srcPurposes.includes(object.id) : true;

				if (addItem) {
					let checked = false;
					let disabled = false;
					if (type === 'purpose_consent') checked = tcModel.purposeConsents.has(object.id);
					if (type === 'purpose_legitimate_interest') checked = tcModel.purposeLegitimateInterests.has(object.id);
					if (type === 'specialfeature') checked = tcModel.specialFeatureOptins.has(object.id);
					if (type === 'feature' || type === 'specialpurpose') checked = disabled = true;

					cmplzRenderCheckbox(type, object, container, checked, disabled);
				}
			}
		}

		//add event listener
		cmplz_tcf_add_event("click", '.cmplz-tcf-' + type + '-input', function (e) {
			const obj = e.target;
			const typeId = parseInt(obj.getAttribute('data-'+type + '_id'));
			const checked = obj.checked;

			document.querySelectorAll('[data-' + type + '_id="' + typeId + '"]').forEach(obj => {
				obj.checked = checked;
			});

			if (type === 'purpose_consent') tcModel.purposeConsents[checked ? 'set' : 'unset'](typeId);
			if (type === 'purpose_legitimate_interest') tcModel.purposeLegitimateInterests[checked ? 'set' : 'unset'](typeId);
			if (type === 'specialfeature') tcModel.specialFeatureOptins[checked ? 'set' : 'unset'](typeId);

			if (checked) cmplzSetTypeByVendor(type, typeId);
			else cmplzUnsetTypeByVendor(type, typeId);

			cmplzUpdateAllVendorLegitimateInterests();
			cmplzSetTCString(tcModel, true);
			cmplz_set_cookie('banner-status', 'dismissed');
		});



		cmplz_tcf_add_event("click", '.cmplz-tcf-toggle', function (e) {
			let obj = e.target;

			e.preventDefault();
			let label = obj.closest('label');
			let description = label.querySelector('.cmplz-tcf-type-description');

			if ( is_hidden(description) ) {
				obj.classList.add('cmplz-tcf-rl');
				obj.classList.remove('cmplz-tcf-rm');
				description.style.display = 'block';
			} else {
				obj.classList.add('cmplz-tcf-rm');
				obj.classList.remove('cmplz-tcf-rl');
				description.style.display = 'none';
			}
		});
	}

	function cmplzUIVisible() {
		let bannerVisible = true;
		const bannerStatus = cmplz_tcf_get_cookie('banner-status');
		if (bannerStatus === 'dismissed') {
			bannerVisible = false;
		}

		const policyVisible = document.getElementById('cmplz-tcf-vendor-container') !== null;
		return bannerVisible || policyVisible;
	}

	/**
	 * Create a list of checkable vendors in the cookie policy
	 * @param vendors
	 */

	function insertVendorsInPolicy(vendors) {
		const vendorContainer = document.getElementById('cmplz-tcf-vendor-container');
		if (vendorContainer === null) {
			return;
		}

		vendorContainer.innerHTML = '';
		const template = document.getElementById('cmplz-tcf-vendor-template').innerHTML;
		const purposes = cmplzFilterArray(cmplzLanguageJson.purposes, cmplz_tcf.purposes);
		const specialPurposes = cmplzFilterArray(cmplzLanguageJson.specialPurposes, cmplz_tcf.specialPurposes);
		const features = cmplzFilterArray(cmplzLanguageJson.features, cmplz_tcf.features);
		const specialFeatures = cmplzFilterArray(cmplzLanguageJson.specialFeatures, cmplz_tcf.specialFeatures);

		generateTypeBlock('purpose_consent', purposes, 'statistics');
		generateTypeBlock('purpose_consent', purposes, 'marketing');
		generateTypeBlock('purpose_legitimate_interest', purposes, 'statistics');
		generateTypeBlock('purpose_legitimate_interest', purposes, 'marketing');
		generateTypeBlock('feature', features, false);
		generateTypeBlock('specialpurpose', specialPurposes, false);
		generateTypeBlock('specialfeature', specialFeatures, false);

		if (specialFeatures.length === 0) {
			document.getElementById('cmplz-tcf-specialfeatures-wrapper').style.display = 'none';
		}
		for (let key in vendors) {
			if (vendors.hasOwnProperty(key)) {
				let customTemplate = template;
				const vendor = vendors[key];
				const vendorPurposes = vendor.purposes.concat(vendor.legIntPurposes);
				let purposeString = '';
				for (const p_key in vendorPurposes) {
					if (vendorPurposes.hasOwnProperty(p_key)) {
						let vendorPurposeId = vendorPurposes[p_key];
						let purposeName = false;
						for (const src_p_key in purposes) {
							if (purposes.hasOwnProperty(src_p_key) && purposes[src_p_key].id === vendorPurposeId) {
								purposeName = purposes[src_p_key].name;
								let defaultRetention = vendor.dataRetention && vendor.dataRetention.hasOwnProperty('stdRetention') ? vendor.dataRetention.stdRetention : null;
								let retention = vendor.dataRetention && vendor.dataRetention.hasOwnProperty(vendorPurposeId) ? vendor.dataRetention[vendorPurposeId] : defaultRetention;
								if ( typeof retention === 'undefined' ) {
									retention = cmplz_tcf.undeclared_string;
								}
								const purposeLink = 'https://cookiedatabase.org/tcf/' + purposeName.replace(/ /g, '-').replace(/\//g, '-').toLowerCase();
								purposeString += '<div class="cmplz-tcf-purpose"><a href="' + purposeLink + '" target="_blank" rel="noopener noreferrer nofollow">' + purposeName + '</a>| '+cmplz_tcf.retention_string+': '+retention+'</div>';
							}
						}
					}
				}

				//get list of categories from dataCategories that exist  in vendor.dataDeclaration
				let categories = [];

				for (const catKey in vendor.dataDeclaration) {
					if (vendor.dataDeclaration.hasOwnProperty(catKey)) {
						let categoryId = vendor.dataDeclaration[catKey];
						let cat = '';
						for (const key in dataCategories) {
							if (dataCategories.hasOwnProperty(key) && dataCategories[key].id === categoryId) {
								cat = dataCategories[key].name;
							}
						}
						categories.push(cat);
					}
				}
				categories = categories.join(', ');

				let retentionInDays = Math.round(vendor.cookieMaxAgeSeconds / (60 * 60 * 24));
				//if result is 0, get day in decimals.
				if (cmplz_tcf.debug) console.log(vendor);
				customTemplate = customTemplate.replace(/{cookie_retention_seconds}/g, vendor.cookieMaxAgeSeconds);
				customTemplate = customTemplate.replace(/{cookie_retention_days}/g, retentionInDays);
				customTemplate = customTemplate.replace(/{vendor_name}/g, vendor.name);
				customTemplate = customTemplate.replace(/{vendor_categories}/g, categories);
				customTemplate = customTemplate.replace(/{vendor_id}/g, vendor.id);
				customTemplate = customTemplate.replace(/{purposes}/g, purposeString);
				//get first array item
				if ( vendor.urls.hasOwnProperty(0)) {
					const url = vendor.urls[0].privacy;
					customTemplate = customTemplate.replace(/{privacy_policy}/g, url);
				}

				const wrapper = document.createElement('div');
				wrapper.innerHTML = customTemplate;
				const checkbox = wrapper.firstChild;
				checkbox.querySelector('.cmplz-tcf-vendor-input').checked = tcModel.vendorConsents.has(vendor.id) || tcModel.vendorLegitimateInterests.has(vendor.id);
				checkbox.querySelector('.cmplz-tcf-vendor-input').setAttribute('data-vendor_id', vendor.id);

				//set consent
				checkbox.querySelector('.cmplz-tcf-consent-input').checked = tcModel.vendorConsents.has(vendor.id);
				checkbox.querySelector('.cmplz-tcf-consent-input').setAttribute('data-vendor_id', vendor.id);

				//show legint option if vendor has legintpurposes
				if (vendor.legIntPurposes.length !== 0) {
					checkbox.querySelector('.cmplz_tcf_legitimate_interest_checkbox').style.display = 'block';
					checkbox.querySelector('.cmplz-tcf-legitimate-interest-input').setAttribute('data-vendor_id', vendor.id);
					checkbox.querySelector('.cmplz-tcf-legitimate-interest-input').checked = tcModel.vendorLegitimateInterests.has(vendor.id);
				}

				//handle non cookie access
				if (vendor.usesNonCookieAccess) {
					wrapper.querySelector('.non-cookie-storage-active').style.display = 'block';
				} else {
					wrapper.querySelector('.non-cookie-storage-inactive').style.display = 'block';
				}

				if (vendor.cookieRefresh) {
					wrapper.querySelector('.non-cookie-refresh-active').style.display = 'block';
				} else {
					wrapper.querySelector('.non-cookie-refresh-inactive').style.display = 'block';
				}

				if (vendor.cookieMaxAgeSeconds <= 0) {
					wrapper.querySelector('.session-storage').style.display = 'block';
				} else if (vendor.cookieMaxAgeSeconds <= 60 * 60 * 24) {
					wrapper.querySelector('.retention_seconds').style.display = 'block';
				} else {
					wrapper.querySelector('.retention_days').style.display = 'block';
				}

				let fragment = document.createDocumentFragment();
				checkbox.classList.add('cmplz-vendortype-tcf');
				fragment.appendChild(checkbox);

				vendorContainer.appendChild(checkbox);
			}
		}

		for (let key in ACVendors) {
			if (ACVendors.hasOwnProperty(key)) {
				let customTemplate = template;
				const vendor = ACVendors[key];
				customTemplate = customTemplate.replace(/{vendor_name}/g, vendor.name);
				customTemplate = customTemplate.replace(/{vendor_id}/g, vendor.id);
				customTemplate = customTemplate.replace(/{privacy_policy}/g, vendor.policyUrl);

				const wrapper = document.createElement('div');
				wrapper.innerHTML = customTemplate;
				const checkbox = wrapper.firstChild;
				checkbox.querySelector('.cmplz-tcf-vendor-input').checked = vendor.consent === 1;
				checkbox.querySelector('.cmplz-tcf-vendor-input').setAttribute('data-ac_vendor_id', vendor.id);

				//set consent
				checkbox.querySelector('.cmplz-tcf-consent-input').checked = vendor.consent === 1;
				checkbox.querySelector('.cmplz-tcf-consent-input').setAttribute('data-ac_vendor_id', vendor.id);

				let fragment = document.createDocumentFragment();
				checkbox.classList.add('cmplz-vendortype-ac');
				fragment.appendChild(checkbox);
				vendorContainer.appendChild(checkbox);
			}
		}

		cmplz_tcf_add_event("click", '.cmplz-tcf-legitimate-interest-input', function (e) {
			let obj = e.target;
			const vendorId = parseInt(obj.getAttribute('data-vendor_id'));
			if ( obj.checked ) {
				tcModel.vendorLegitimateInterests.set(vendorId);
				let container = obj.closest('.cmplz-tcf-vendor-container');
				container.querySelector('.cmplz-tcf-vendor-input').checked = true;
			} else {
				tcModel.vendorLegitimateInterests.unset(vendorId);
			}

			cmplzSetTCString(tcModel, true);
			cmplz_set_cookie('banner-status', 'dismissed');
		});

		cmplz_tcf_add_event("click", '.cmplz-tcf-consent-input', function (e) {
			let obj = e.target;
			const vendorId = parseInt(obj.getAttribute('data-vendor_id'));
			let container = obj.closest('.cmplz-tcf-vendor-container');
			if (vendorId) {
				if (obj.checked) {
					tcModel.vendorConsents.set(vendorId);
					container.querySelector('.cmplz-tcf-vendor-input').prop('checked', true);
				} else {
					tcModel.vendorConsents.unset(vendorId);
					container.querySelector('.cmplz-tcf-vendor-input').prop('checked', false);
				}
			}

			const ACVendorId = parseInt(obj.getAttribute('data-ac_vendor_id'));
			if (ACVendorId){
				if (obj.checked) {
					consentACVendor(ACVendorId);
					container.querySelector('.cmplz-tcf-vendor-input').prop('checked', true);
				} else {
					denyACVendor(ACVendorId);
					container.querySelector('.cmplz-tcf-vendor-input').prop('checked', false);
				}
			}
			//now we update the tcstring
			cmplzSetTCString(tcModel, true);
			cmplz_set_cookie('banner-status', 'dismissed');
		});

		cmplz_tcf_add_event("click", '.cmplz-tcf-vendor-input', function (e) {
			let obj = e.target;
			const vendorId = parseInt(obj.getAttribute('data-vendor_id'));
			const ACVendorId = parseInt(obj.getAttribute('data-ac_vendor_id'));
			let container = obj.closest('.cmplz-tcf-vendor-container');
			if (vendorId){
				if (obj.checked) {
					tcModel.vendorConsents.set(vendorId);
					//positive leg int should not be set.
					tcModel.vendorLegitimateInterests.set(vendorId);
					container.querySelector('.cmplz-tcf-legitimate-interest-input' ).checked = true;
					container.querySelector('.cmplz-tcf-consent-input' ).checked = true;
				} else {
					tcModel.vendorConsents.unset(vendorId);
					tcModel.vendorLegitimateInterests.unset(vendorId);
					container.querySelector('.cmplz-tcf-legitimate-interest-input').checked = false;
					container.querySelector('.cmplz-tcf-consent-input').checked = false;
				}
			} else if (ACVendorId){
				if (obj.checked) {
					consentACVendor(ACVendorId);
					container.querySelector('.cmplz-tcf-consent-input' ).checked = true;
				} else {
					denyACVendor(ACVendorId);
					container.querySelector('.cmplz-tcf-consent-input').checked = false;
				}
			}
			cmplzSetTCString(tcModel, true);
			cmplz_set_cookie('banner-status', 'dismissed');
		});

		cmplz_tcf_add_event("click", '.cmplz-tcf-toggle-info', function (e) {
			let obj = e.target;
			e.preventDefault();
			if ( is_hidden() ) {
				obj.style.display = 'block';
			} else {
				obj.style.display = 'none';
			}
		});

		cmplz_tcf_add_event("click", '.cmplz-tcf-toggle-vendor', function (e) {
			let obj = e.target;
			e.preventDefault();
			const container = obj.closest('.cmplz-tcf-vendor-container');
			const info = container.querySelector('.cmplz-tcf-info');
			if ( is_hidden(info) ) {
				obj.classList.add('cmplz-tcf-rl');
				obj.classList.remove('cmplz-tcf-rm');
				info.style.display = 'block';
			} else {
				obj.classList.add('cmplz-tcf-rm');
				obj.classList.remove('cmplz-tcf-rl');
				info.style.display = 'none';
			}
		});

		cmplz_tcf_add_event("click", "#cmplz-tcf-selectall", function () {
			for (let key in vendors) {
				if (vendors.hasOwnProperty(key)) {
					const vendor = vendors[key];
					tcModel.vendorConsents.set(vendor.id);
					document.querySelector('#cmplz-tcf-' + vendor.id).checked = true;
				}
			}
			const vendorCheckboxes = document.querySelectorAll('[data-vendor_id]');
			vendorCheckboxes.forEach(vendorCheckbox => {
				vendorCheckbox.checked = true;
			});
			acceptAllVendors();
		});

		cmplz_tcf_add_event("click", "#cmplz-tcf-deselectall", function () {
			for (let key in vendors) {
				if (vendors.hasOwnProperty(key)) {
					const vendor = vendors[key];
					tcModel.vendorConsents.unset(vendor.id);
					document.querySelector('#cmplz-tcf-' + vendor.id).checked = false;
				}
			}
			revokeAllVendors(true);
		});
		let event = new CustomEvent('cmplz_vendor_container_loaded', {detail: complianz.region});
		document.dispatchEvent(event);
	}

	/**
	 * Filter the list of vendors
	 *
	 * @param vendors
	 * @returns {*}
	 */
	function cmplzFilterVendors(vendors) {
		let vendorIds = Object.values(vendors).map(vendor => vendor.id);

		let addVendorIds = cmplzFilterVendorsBy('purposes', vendors, cmplz_tcf.purposes);
		vendorIds = vendorIds.filter(value => addVendorIds.includes(value));
		addVendorIds = cmplzFilterVendorsBy('specialPurposes', vendors, cmplz_tcf.specialPurposes);
		vendorIds = vendorIds.filter(value => addVendorIds.includes(value));

		addVendorIds = cmplzFilterVendorsBy('features', vendors, cmplz_tcf.features);
		vendorIds = vendorIds.filter(value => addVendorIds.includes(value));
		addVendorIds = cmplzFilterVendorsBy('specialFeatures', vendors, cmplz_tcf.specialFeatures);
		vendorIds = vendorIds.filter(value => addVendorIds.includes(value));
		//remove all vendors that are included in cmplz_tcf.excludedVendors
		//convert cmplz_tcf.excludedVendors json to array
		let excludedVendors = Object.keys(cmplz_tcf.excludedVendors).map(function(key) {
			return cmplz_tcf.excludedVendors[key];
		});

		vendorIds = vendorIds.filter(value => !excludedVendors.includes(value));

		return vendorIds;
	}

	/**
	 * Get all vendors who use this purpose
	 * @param type
	 * @param vendors
	 * @param category_purposes
	 * @returns {[]}
	 */
	function cmplzGetVendorsWithPurpose(type, vendors, category_purposes) {
		let output = [];
		for (const vendor in vendors) {
			for (const purpose in category_purposes) {
				if (vendors[vendor][type].includes(category_purposes[''+purpose])) {
					output.push(vendors[vendor].id);
					break;
				}
			}
		}
		return output;
	}


	/**
	 * Get vendors who only have one of these purposes
	 * @param type
	 * @param vendors
	 * @param category_purposes
	 * @returns {[]}
	 */
	// function cmplzFilterVendorsBy(type, vendors, category_purposes) {
	// 	let output = [];
	// 	for (let key in vendors) {
	// 		if (vendors.hasOwnProperty(key)) {
	// 			const vendor = vendors[key];
	// 			//for each vendor purpose, check if it exists in the category purposes list. If not, don't add this vendor
	// 			let allPurposesAreCategoryPurpose = true;
	// 			const vendorProperties = vendor[type];
	// 			for (let p_key in vendorProperties) {
	// 				if (vendorProperties.hasOwnProperty(p_key)) {
	// 					const purpose = vendorProperties[p_key];
	// 					const inPurposeArray = category_purposes.includes(purpose);
	// 					if (!inPurposeArray) {
	// 						allPurposesAreCategoryPurpose = false;
	// 					}
	// 				}
	// 			}
	// 			const inOutPutArray = output.includes(vendor.id);
	// 			if (!inOutPutArray && allPurposesAreCategoryPurpose) {
	// 				output.push(vendor.id);
	// 			}
	// 		}
	// 	}
	// 	return output;
	// }

	function cmplzFilterVendorsBy(type, vendors, category_purposes) {
		const output = [];

		for (const vendor of Object.values(vendors)) {
			const vendorProperties = vendor[type];

			if (Object.values(vendorProperties).every(purpose => category_purposes.includes(purpose))) {
				output.push(vendor.id);
			}
		}

		return output;
	}

	/**
	 * Get thet TC String
	 * @returns {string}
	 */
	function cmplzGetTCString() {
		let user_policy_id = cmplz_tcf_get_cookie('policy_id');
		if ( !user_policy_id || (typeof complianz!=='undefined' && complianz.current_policy_id !== user_policy_id)  ) {
			if (localStorage.cmplz_tcf_consent) localStorage.removeItem('cmplz_tcf_consent');
		}
		return window.localStorage.getItem('cmplz_tcf_consent');
	}

	/**
	 * Get thet TC String
	 * @returns {string}
	 */
	function cmplzGetACString() {
		return window.localStorage.getItem('cmplz_ac_string');
	}

	/**
	 * Set the tc string, and update the api if needed
	 */
	function cmplzSetACString() {
		// skip if the ACVendors array objects do not have a consent attribute
		if (ACVendors.length===0 || typeof ACVendors[0].consent === 'undefined') return;

		let ACversion = 1;
		let ACString = ACversion + '~';
		//filter out all vendors where the 'consent' attribute is 0
		let consentedVendors = ACVendors.filter((vendor) => {
			return vendor.consent === 1;
		})

		//delete if no consent was given
		if (consentedVendors.length===0) {
			if (localStorage.cmplz_ac_string) localStorage.removeItem('cmplz_ac_string');
			return
		}

		//get an array of vendor id's, and join it.
		ACString += consentedVendors.map((vendor) => {
			return vendor.id;
		}).join('.');

		window.localStorage.setItem('cmplz_ac_string', ACString);
	}

	/**
	 * Set the tc string, and update the api if needed
	 * @param tcModel
	 * @param uiVisible
	 */
	function cmplzSetTCString( tcModel, uiVisible ) {
		cmplzSetACString();
		let encodedTCString = null;
		if ( tcModel ) {
			tcModel.created = cmplzRemoveTime(tcModel.lastUpdated);
			tcModel.lastUpdated = cmplzRemoveTime(tcModel.lastUpdated);
			encodedTCString = TCString.encode(tcModel);
		}
		// __tcfapi('getTCData', 2, (tcData, success) => {}, [tcModel.vendors]);

		cmpApi.update(encodedTCString, uiVisible);
		window.localStorage.setItem('cmplz_tcf_consent', encodedTCString);
	}

	/**
	 * Ensure the date does not contain hours or minutes
	 * @param date
	 * @returns {Date}
	 */

	function cmplzRemoveTime(date) {
		return new Date(
			date.getFullYear(),
			date.getMonth(),
			date.getDate()
		);
	}

	/**
	 * Get list of purposes
	 * @param category
	 * @param includeLowerCategories
	 * @returns {*[]|number[]}
	 */
	function getPurposes(category, includeLowerCategories) {
		//these categories aren't used
		if (category === 'functional' || category === 'preferences') {
			return [];
		}

		if (category === 'marketing') {
			if (includeLowerCategories) {
				return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
			} else {
				return [1, 2, 3, 4, 5, 6, 10, 11];
			}
		} else if (category === 'statistics') {
			return [1, 7, 8, 9];
		}
	}

	/**
	 * Check if a region is a TCF region
	 * @param region
	 * @returns {boolean}
	 */
	function cmplz_is_tcf_region(region) {
		return !!cmplz_in_array(region, complianz.tcf_regions);

	}

	function configureOptinBanner() {
		//don't do this for non TCF regions
		if (!cmplz_is_tcf_region(complianz.region)) {
			return;
		}
		/**
		 * Filter purposes based on passed purposes
		 */
		//only optin variant of tcf has these purposes on the banner.
		if ( complianz.consenttype === 'optin' ) {
			const srcMarketingPurposes = getPurposes('marketing', false);
			const srcStatisticsPurposes = getPurposes('statistics', false);
			const marketingPurposes = cmplzFilterArray(cmplzFilterArray(cmplzLanguageJson.purposes, cmplz_tcf.purposes), srcMarketingPurposes);
			const statisticsPurposes = cmplzFilterArray(cmplzFilterArray(cmplzLanguageJson.purposes, cmplz_tcf.purposes), srcStatisticsPurposes);
			const features = cmplzFilterArray(cmplzLanguageJson.features, cmplz_tcf.features);
			const specialPurposes = cmplzFilterArray(cmplzLanguageJson.specialPurposes, cmplz_tcf.specialPurposes);
			const specialFeatures = cmplzFilterArray(cmplzLanguageJson.specialFeatures, cmplz_tcf.specialFeatures);

			if (features.length === 0) document.querySelector('.cmplz-tcf .cmplz-features').style.display = 'none';
			if (specialPurposes.length === 0) document.querySelector('.cmplz-tcf .cmplz-specialpurposes').style.display = 'none';
			if (specialFeatures.length === 0) document.querySelector('.cmplz-tcf .cmplz-specialfeatures').style.display = 'none';
			if (statisticsPurposes.length === 0) document.querySelector('.cmplz-tcf .cmplz-statistics').style.display = 'none';
			document.querySelector('.cmplz-tcf .cmplz-statistics .cmplz-description').innerHTML = cmplzConcatenateString(statisticsPurposes);
			document.querySelector('.cmplz-tcf .cmplz-marketing .cmplz-description').innerHTML = cmplzConcatenateString(marketingPurposes);
			document.querySelector('.cmplz-tcf .cmplz-features .cmplz-description').innerHTML = cmplzConcatenateString(features);
			document.querySelector('.cmplz-tcf .cmplz-specialfeatures .cmplz-title').innerHTML = cmplzConcatenateString(specialFeatures);
			document.querySelector('.cmplz-tcf .cmplz-specialpurposes .cmplz-title').innerHTML = cmplzConcatenateString(specialPurposes);
		}

		let vendorCountContainers = document.querySelectorAll('.cmplz-manage-vendors.tcf');
		if ( vendorCountContainers ) {
			let count = complianz.consenttype === 'optin' ? tcModel.gvl.vendorIds.size : '';
			if ( useAcVendors && complianz.consenttype === 'optin' ){
				count+= ACVendors.length;
			}
			vendorCountContainers.forEach(obj => {
				obj.innerHTML = obj.innerHTML.replace('{vendor_count}', count);
			});
		}

		//on pageload, show vendorlist area
		let wrapper = document.getElementById('cmplz-tcf-wrapper');
		let noscript_wrapper = document.getElementById('cmplz-tcf-wrapper-nojavascript');
		if ( wrapper ){
			wrapper.style.display = 'block';
			noscript_wrapper.style.display = 'none';
		}
	}

	function cmplzFilterArray(arrayToFilter, arrayToFilterBy) {
		let output = [];
		for (let key in arrayToFilter) {
			if (arrayToFilterBy.includes(''+arrayToFilter[key].id) || arrayToFilterBy.includes(arrayToFilter[key].id)) {
				output.push(arrayToFilter[key]);
			}
		}
		return output;
	}

	function cmplzConcatenateString(array) {
		let string = '';
		const max = array.length - 1;
		for (let key in array) {
			if (array.hasOwnProperty(key)) {
				string += array[key].name;
				if (key < max) {
					string += ', ';
				} else {
					string += '.';
				}
			}
		}
		return string;
	}

});

/**
 * TCF for CCPA
 */

const USPSTR_NN = "1NN";
const USPSTR_YN = "1YN";
const USPSTR_YY = "1YY";
const USPSTR_NA = "1---";
let ccpaVendorlistLoadedResolve;
let ccpaVendorlistLoaded = new Promise(function(resolve, reject){
	ccpaVendorlistLoadedResolve = resolve;
});
let USvendorlistUrl = cmplz_tcf.cmp_url + 'cmp/vendorlist' + '/lspa.json';
let ccpaVendorList;
bannerDataLoaded.then(()=> {
	if (complianz.consenttype === 'optout' || onOptOutPolicyPage) {
		fetch(USvendorlistUrl, {
			method: "GET",
		}).then(response => response.json())
			.then(
				function (data) {
					ccpaVendorList = data;
					ccpaVendorlistLoadedResolve();
				}
			);
		cmplz_set_ccpa_tc_string();
		cmplzRenderUSVendorsInPolicy();
	} else {
		if (cmplz_tcf.debug) console.log("not an optout tcf region or page");
	}
});

/**
 * When CCPA applies, we set the TC string in the usprivacy cookie
 */
function cmplz_set_ccpa_tc_string() {
	if ( cmplz_tcf.ccpa_applies ) {
		cmplz_set_cookie('usprivacy', USPSTR_YN + cmplz_tcf.lspact, false);
		document.addEventListener("cmplz_fire_categories", function (e) {
			let val = USPSTR_YY + cmplz_tcf.lspact;
			if (cmplz_in_array('marketing', e.detail.categories)) {
				val = USPSTR_YN + cmplz_tcf.lspact;
			}
			cmplz_set_cookie('usprivacy', val, false);
		});
	} else {
		cmplz_set_cookie('usprivacy', USPSTR_NA + cmplz_tcf.lspact, false );
	}
}

function cmplzRenderUSVendorsInPolicy() {
	ccpaVendorlistLoaded.then(()=> {
		let vendors = ccpaVendorList.signatories;
		const vendorContainer = document.getElementById('cmplz-tcf-us-vendor-container');

		if (vendorContainer === null) {
			return;
		}
		vendorContainer.innerHTML = '';
		const template = document.getElementById('cmplz-tcf-vendor-template').innerHTML;

		for (let key in vendors) {
			if (vendors.hasOwnProperty(key)) {
				let customTemplate = template;
				let vendor = vendors[key];
				customTemplate = customTemplate.replace(/{vendor_name}/g, vendor.signatoryLegalName);
				let hasOptoutUrl = true;
				if (vendor.optoutUrl.indexOf('http') === -1) {
					hasOptoutUrl = false;
					customTemplate = customTemplate.replace(/{optout_string}/g, vendor.optoutUrl);
				} else {
					customTemplate = customTemplate.replace(/{optout_url}/g, vendor.optoutUrl);
				}

				const wrapper = document.createElement('div');
				wrapper.innerHTML = customTemplate;
				const html = wrapper.firstChild;
				if (hasOptoutUrl) {
					html.querySelector('.cmplz-tcf-optout-string').style.display = 'none';
					html.querySelector('.cmplz-tcf-optout-url').style.display = 'block';

				} else {
					html.querySelector('.cmplz-tcf-optout-string').style.display = 'block';
					html.querySelector('.cmplz-tcf-optout-url').style.display = 'none';
				}
				let fragment = document.createDocumentFragment();
				fragment.appendChild(html);
				vendorContainer.appendChild(html);
			}
		}

		document.querySelector('#cmplz-tcf-wrapper').style.display = 'block';
		document.querySelector('#cmplz-tcf-wrapper-nojavascript').style.display = 'none';
	})
}

/**
 * Parses a CSV row into an array of values.
 *
 * This function uses a regular expression to match values in a CSV row.
 * It handles values enclosed in double quotes and values separated by commas.
 *
 * @returns {Array} An array of values parsed from the CSV row.
 */
function cmplzParseCsvRow(row) {
	const regex = /"(.*?)"|([^,]+)/g;
	const values = [];
	let match;
	while ((match = regex.exec(row)) !== null) {
		values.push(match[1] || match[2]);
	}
	return values;
}

/**
 * @todo get a list of ddr.js files. Not currently available
 * https://github.com/InteractiveAdvertisingBureau/USPrivacy/issues/17
 */
// Add all Vendor scripts; this is just an array of string sources
//https://github.com/InteractiveAdvertisingBureau/USPrivacy/blob/master/CCPA/Data%20Deletion%20Request%20Handling.md
// vendorDeleteScriptSources.forEach((vendorDeleteScriptSource) => {
//
// 	const scriptElement = document.createElement("script");
// 	scriptElement.src = vendorDeleteScriptSource;
//
// 	document.body.appendChild(scriptElement);
//
// });

/**
 * Fire a data deletion request.
 */
document.addEventListener("cmplz_dnsmpi_submit", function (e) {
	if (cmplz_tcf.debug) console.log("fire data deletion request for TCF");
	__uspapi('performDeletion', 1);
});
