﻿=== Skaut bazar ===
Contributors: skaut, kalich5, davidulus, rbrounek, kulikjak
Tags: bazar, skaut, multisite, plugin, shortcode, 
Requires at least: 4.0
Tested up to: 5.0
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Implementace jednoduchého bazaru s možností online rezervace přes email

== Description ==

Implementace jednoduchého bazaru s možností online rezervace přes email

Plugin po aktivaci se vkládá na libovolnou stránku pomocí Shortcodes: **[skautbazar]**

Plugin podporuje i MultiSite, takže můžete mít na každé stránce jiný bazar, s vlastním nastavením a vším co je s tím spojené. V nastavení je možnost výrozích hodnot. Tedy jméno, přijímení, email a telefon. Požadovaný je vše kromě telefonu.
Při zakládání nového inzerátu, jsou požadovaná pole označena kvězdičkou.

**Uživatelské role**

Možnost vytvořit roli "Bazar" (nebo role) pomocí některého pluginu na vytváření rolí [WPFront User Role Editor](https://cs.wordpress.org/plugins/wpfront-user-role-editor/) nebo [User Role Editor](https://cs.wordpress.org/plugins/user-role-editor/), která může mít práva jen k inzerátům.

**Nápady na nové fukce**

Máte nějaký nápad, napište prosím do místního fóra. Pokusíme se když tak zapraovat
[https://cs.wordpress.org/support/plugin/skaut-bazar](https://cs.wordpress.org/support/plugin/skaut-bazar)

== Installation ==

Instalace je jednoduchá.

1. Stáhnout si plugin a aktivovat
2. Výchozí nastavení "nastavení --> Skaut bazar"
	V tomto nastavní si nastavte základní informace o sobě
3. Vložte na stránku kde chcete mít výpis inzerátů shordtcode: **[skautbazar]**

== Frequently Asked Questions ==

**Jak plugin správně nastavit?**

Musí se v "Nastavení" a najít tam položku "Skaut bazar" a tam je výchozí nastavení.

Dá se tam zadat jméno, přijímení, email, telefon, měnu a počáteční číslo inzereátu.

Možnost vytvořit roli "Bazar" (nebo role) pomocí některého pluginu na vytváření rolí [WPFront User Role Editor](https://cs.wordpress.org/plugins/wpfront-user-role-editor/) nebo [User Role Editor](https://cs.wordpress.org/plugins/user-role-editor/), která může mít práva jen k inzerátům.

**podpora pluginu**

Oficiální podpora je na [http://dobryweb.skauting.cz/](http://dobryweb.skauting.cz/)

**Jsme na GitHubu**

[https://github.com/skaut/skaut-bazar](https://github.com/skaut/skaut-bazar)

== Screenshots ==

1. Zobrazení na stránkách
2. Výpis všech položek bazaru
3. Založení nové položky
4. Nastavení

== Changelog ==

= 1.3 =
* přidána možnost zaslat nabízejícímu zprávu
* přidána možnost skrýt příjmení
* automatické vyplňování jména a emailu do formulářů
* oprava menších glitchů

= 1.2 =
* oprava uživatelských rolí a jejich přidávání
* po smazání pluginu, se smažou i role které byly pluginem vytvořené

= 1.1 =
* přidání možnosti vytvářet si role k pluginu

= 1.0.4 =
* ikonka

= 1.0.3 =
* přidána úvodní fotky
* screenshoty

= 1.0.2 =
* opravy na wordpress.org 2

= 1.0.1 =
* opravy na wordpress.org

= 1.0 =
* možnost zakládat inzeráty
* rezervace přes email
* podpora MultiSite
* podpora rubrik a štítků
* překlad EN a CZ
* podpora shortcodes
