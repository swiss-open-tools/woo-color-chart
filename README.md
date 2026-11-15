# Color Chart for WooCommerce

<!-- Hostinger button: format taken from https://www.hostinger.com/open-source-partners (checked 2026-11-01).
     TODO before publishing: replace the repo path if different and YOUR_PARTNER_CODE with the real partner code from hPanel. -->
[![Deploy on Hostinger](https://assets.hostinger.com/vps/deploy.svg)](https://www.hostinger.com/deploy?template=swiss-open-tools/woo-color-chart&ref=YOUR_PARTNER_CODE)

**[Deutsch](#deutsch) | [English](#english)**

---

## Deutsch

Zeigt alle Farben eines variablen WooCommerce-Produkts als **Farbkarte**: Farbfeld (Hex), Farbname, Farbcode/SKU, Lagerstatus, Preis und „In den Warenkorb“ direkt pro Farbe. Gemacht für Shops mit vielen Farbvarianten: Spraydosen, Marker, Acryl- und Ölfarben, Garn, Nagellack, Filament.

Anders als Swatch-Plugins (die das Dropdown durch Farbkreise ersetzen, man wählt eine Farbe nach der anderen) zeigt die Farbkarte **alle Farben gleichzeitig wie eine gedruckte Herstellerkarte**. Kundinnen und Kunden können mehrere Farben nacheinander in den Warenkorb legen, ohne die Seite zu verlassen.

### Funktionen
- Tabelle auf dem Desktop, Kachelraster auf dem Handy
- Sortierung nach Farbton (wie eine Farbkarte), Name oder Code
- Filter nach Farbfamilie (Rot, Orange, Gelb, Grün, Türkis, Blau, Violett, Pink, Braun, Grau, Schwarz, Weiss), automatisch aus dem Hex-Wert berechnet
- Suche nach Farbname oder Code (ignoriert Gross/Klein und Umlaute)
- Lagerstatus mit Ampel (an Lager / wenig / ausverkauft / Rückstand)
- Mengenfeld + Warenkorb per AJAX; funktioniert ohne JavaScript als normales Formular
- Shortcode `[color_chart]` und Gutenberg-Block „Color Chart“
- Optional automatisch auf der Produktseite anzeigen (Häkchen im Produkt)
- Hex-Wert pro Attribut-Begriff (z. B. *Farbe → Signal Yellow*) oder pro Variation (überschreibt)

### Installation
1. Ordner `woo-color-chart/` nach `wp-content/plugins/` kopieren (oder als ZIP hochladen) und aktivieren. WooCommerce muss aktiv sein.
2. **Produkte → Eigenschaften → Farbe → Begriffe bearbeiten**: pro Farbe einen Hex-Wert setzen. Alternativ im Variationen-Tab eines Produkts.
3. Im Produkt (Tab „Allgemein“) „Colour chart“ anhaken **oder** den Shortcode/Block irgendwo einfügen.

### Shortcode
```
[color_chart id="123" sort="hue" filter="yes" search="yes" stock="yes"]
```
| Parameter | Werte | Standard |
|---|---|---|
| `id` | Produkt-ID (leer = aktuelles Produkt) | aktuelles |
| `sort` | `hue`, `name`, `sku`, `menu` (WooCommerce-Reihenfolge) | `hue` |
| `filter` / `search` / `stock` | `yes` / `no` | `yes` |

Das Farbattribut wird automatisch erkannt (Name enthält *farbe, color, colour, couleur …*). Abweichend: Filter `wcc_color_attribute`. Zeilen anpassen: Filter `wcc_rows`.

### Demo mit Docker
```bash
cp .env.example .env   # alle Passwörter ändern!
docker compose up -d
# nach ca. 1–2 Minuten: http://localhost:8080/product/demo-spray-400ml/  und  /farbkarte/
docker compose logs setup   # Fortschritt der Einrichtung
```
Die Demo installiert WordPress, WooCommerce, dieses Plugin und ein Beispielprodukt mit 16 Farben (Beispieldaten, keine offiziellen Herstellerwerte).

### Tests
```bash
php tests/test-color.php        # Farbmathematik (ohne WordPress)
php tests/smoke-render.php      # Ausgabe mit WordPress-Stubs
node tests/test-color-chart.js  # Filter/Sortierung im Frontend
```

---

## English

Displays all colours of a variable WooCommerce product as a **colour chart**: swatch (hex), colour name, colour code/SKU, stock status, price and add-to-cart per colour. Built for shops with many colour variations: spray paint, markers, acrylic and oil paint, yarn, nail polish, filament.

Unlike swatch plugins (which replace the dropdown with colour dots, one selection at a time), the colour chart shows **every colour at once, like a printed manufacturer chart**, and lets customers add several colours to the cart without leaving the page.

### Features
- Table on desktop, card grid on mobile
- Sort by hue (chart order), name or code
- Filter by colour family, computed automatically from the hex value
- Search by name or code (case and accent insensitive)
- Stock indicator (in stock / low / out of stock / backorder)
- Quantity + AJAX add to cart; degrades to a plain form without JavaScript
- Shortcode `[color_chart]` and block "Color Chart"
- Optional automatic display on the product page (checkbox per product)
- Hex value per attribute term or per variation (variation wins)

### Installation
1. Copy `woo-color-chart/` to `wp-content/plugins/` and activate (WooCommerce required).
2. **Products → Attributes → Colour → Configure terms**: set a hex value per colour, or per variation in the product's Variations tab.
3. Tick "Colour chart" in the product's General tab **or** place the shortcode/block anywhere.

Shortcode parameters: see the table in the German section (`id`, `sort`, `filter`, `search`, `stock`). Hooks: `wcc_color_attribute`, `wcc_rows`.

### Docker demo
```bash
cp .env.example .env   # change every password!
docker compose up -d
# open http://localhost:8080/product/demo-spray-400ml/ and /farbkarte/
```

### Deploy on Hostinger
The button above opens Hostinger's one-click deploy for this repository (Docker Compose on a VPS). After deploying, set the values from `.env.example` (especially passwords and `WP_URL`). Disclosure: the button contains a partner referral code; the project earns a commission if you buy hosting through it, and you get a discount on your first purchase.

## License
GPL-2.0-or-later, see [LICENSE](LICENSE).
