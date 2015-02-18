/**
 * "tplcfg_edit_form" javascript extension
 * 
 * add some convenience to CONTENIDO's template configuration form ^^
 * - collapse/expand config panels
 * - autosubmit on SELECT, RADIO and CHECKBOX change
 * 
 * @author	Björn Bartels <info@dragon-projects.de>
 * @package CONTENIDO Plugin and Extension API
 * 
 * @uses	jQuery
 */
(function ($) {
	var formSelector = '#tpl_form',
		moduleSelector = 'TR.peaModule';
	
	$(document).ready(function () {
		var oCfgForm = $(formSelector),
			oCfgTpl = oCfgForm.find('TR:first-child'),
			oModules = oCfgForm.find(moduleSelector),
			// classnames
			cCfgPanel = 'peaModuleConfigPanel',
			cModActive = 'active',
			
			createButtons = function ( oMod ) {
				var oPanel = $([
				    '<span class="peaModuleButtonPanel">',
				    	'<span class="peaModuleButton peaIcon peaIconSettings" data-action="open">','</span>',
				    	'<span class="peaModuleButton peaIcon peaIconSave" data-action="save">','</span>',
				    	'<span class="peaModuleButton peaIcon peaIconClose" data-action="close">','</span>',
				    '</span>',
				''].join(''));
				oMod.find('TD + TD').first().append(oPanel);
			},
		
			toggleModuleConfigPanel = function ( oMod ) {
				oMod.toggleClass('active');
			},
			
			getActiveModulesRequest = function ( ) {
				var url = location.href,
					result = ( '' || ( String( '' || ( String(url).split('pea=', 2)[1] ) ).split('&', 2)[0] ) )
				;
				return ((result == undefined) || (result == '')) ? [] : String(result).split(',');
			},
			
			readActiveModulePanels = function ( ) {
				var result = [];
				oCfgForm.find(moduleSelector).each(function (iPanel, ePanel) { result.push( $(ePanel).hasClass(cModActive) ); });
				return result;
			},
			
			openModule = function ( aiMods ) {
				$(aiMods).each(function (iPanel, bActive) {
					if (bActive) toggleModuleConfigPanel( oModules[iPanel] );
				});
			},
			
			initModule = function ( eMod ) {
				var oMod = $(eMod),
					sModName = oMod.find('TD + TD').first().html(),
					oCfg = oMod.next();
				
				oMod.removeClass(cModActive);
				oCfg.addClass(cCfgPanel);
				
				createButtons( oMod );
				
			},
			
			actives = getActiveModulesRequest()
			
		;
		
		oModules.each(function(iMod, eMod){
			initModule( eMod );
			var $eModPanel = $(eMod);
			if (actives[iMod] == 'true') toggleModuleConfigPanel($eModPanel);
			
			$eModPanel.find('.peaModuleButton').bind('click.con_pea', function (oEvent) {
				var oMod = $(this).parents(moduleSelector).first(),
					sAction = $(this).data('action')
				;
				
				switch (sAction) {
					case 'open' :
					case 'close' : toggleModuleConfigPanel(oMod); break;
					case 'save' : $(formSelector).submit(); break;
					default : break;
				}
			});
			
			$eModPanel.next().find('SELECT').bind('change.con_pea', function (oEvent) {
				oCfgForm.submit();
			});
			
			$eModPanel.next().find('INPUT[type=radio],INPUT[type=checkbox]').bind('click.con_pea', function (oEvent) {
				oCfgForm.submit();
			});
			
			oCfgForm.bind('submit.con_pea', function (oEvent) {
				var action = oCfgForm.attr('action'),
					modules = (readActiveModulePanels()).join(',')
				;
				oCfgForm.attr('action', action + ('&pea='+modules) );
			});
		});
		
	});
	
})(jQuery);