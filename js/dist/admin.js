module.exports =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./admin.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./admin.js":
/*!******************!*\
  !*** ./admin.js ***!
  \******************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _src_admin__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/admin */ "./src/admin/index.js");
/* empty/unused harmony star reexport */

/***/ }),

/***/ "./src/admin/index.js":
/*!****************************!*\
  !*** ./src/admin/index.js ***!
  \****************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var flarum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/app */ "flarum/app");
/* harmony import */ var flarum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _fof_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @fof-components */ "@fof-components");
/* harmony import */ var _fof_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_fof_components__WEBPACK_IMPORTED_MODULE_1__);


var SettingsModal = _fof_components__WEBPACK_IMPORTED_MODULE_1__["settings"].SettingsModal,
    _settings$items = _fof_components__WEBPACK_IMPORTED_MODULE_1__["settings"].items,
    BooleanItem = _settings$items.BooleanItem,
    SelectItem = _settings$items.SelectItem;
flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.initializers.add('fof/sitemap', function () {
  flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.extensionSettings['fof-sitemap'] = function () {
    return flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.modal.show(new SettingsModal({
      title: flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('fof-sitemap.admin.settings.title'),
      type: 'medium',
      items: [m("div", {
        className: "Form-group"
      }, m("label", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_label")), SelectItem.component({
        options: {
          'run': flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('fof-sitemap.admin.settings.modes.runtime'),
          'cache': flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('fof-sitemap.admin.settings.modes.cache'),
          'cache-disk': flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('fof-sitemap.admin.settings.modes.cache_disk'),
          'multi-file': flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('fof-sitemap.admin.settings.modes.multi_file')
        },
        key: "fof-sitemap.mode",
        required: false
      })), m("p", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help")), m("div", null, m("h3", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help_runtime_label")), m("p", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help_runtime"))), m("h4", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help_schedule")), m("div", null, m("h3", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help_cache_disk_label")), m("p", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help_cache_disk"))), m("h4", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help_large")), m("div", null, m("h3", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help_multi_label")), m("p", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.mode_help_multi"))), m("hr", null), m("h3", null, flarum_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("fof-sitemap.admin.settings.advanced_options_label"))]
    }));
  };
});

/***/ }),

/***/ "@fof-components":
/*!******************************************************!*\
  !*** external "flarum.extensions['fof-components']" ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.extensions['fof-components'];

/***/ }),

/***/ "flarum/app":
/*!********************************************!*\
  !*** external "flarum.core.compat['app']" ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['app'];

/***/ })

/******/ });
//# sourceMappingURL=admin.js.map