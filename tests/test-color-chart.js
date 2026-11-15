// Node unit tests for the pure functions of assets/js/color-chart.js.
// Run: node tests/test-color-chart.js
'use strict';
const assert = require( 'assert' );
const wcc = require( '../woo-color-chart/assets/js/color-chart.js' );

const rows = [
	{ family: 'red', name: 'Traffic Red', sku: 'DEMO-3000', hue: '355.2' },
	{ family: 'blue', name: 'Ultramarin Blau', sku: 'DEMO-5000', hue: '225.4' },
	{ family: 'green', name: 'Grün', sku: 'DEMO-6100', hue: '114.1' },
	{ family: 'black', name: 'Black', sku: 'DEMO-10', hue: '1093' },
];
let n = 0;
const t = ( name, fn ) => { fn(); n++; };

t( 'no filter matches all', () => assert.strictEqual( rows.filter( r => wcc.matches( r, { family: '', query: '' } ) ).length, 4 ) );
t( 'family filter', () => assert.deepStrictEqual( rows.filter( r => wcc.matches( r, { family: 'blue', query: '' } ) ).map( r => r.name ), [ 'Ultramarin Blau' ] ) );
t( 'search by sku', () => assert.deepStrictEqual( rows.filter( r => wcc.matches( r, { family: '', query: '6100' } ) ).map( r => r.name ), [ 'Grün' ] ) );
t( 'search ignores accents/case', () => assert.ok( wcc.matches( rows[2], { family: '', query: 'GRUN' } ) ) );
t( 'family + search combined', () => assert.ok( ! wcc.matches( rows[0], { family: 'blue', query: 'red' } ) ) );
t( 'sort by hue', () => assert.deepStrictEqual( rows.slice().sort( ( a, b ) => wcc.compare( a, b, 'hue' ) ).map( r => r.family ), [ 'green', 'blue', 'red', 'black' ] ) );
t( 'sort by sku numeric', () => assert.deepStrictEqual( rows.slice().sort( ( a, b ) => wcc.compare( a, b, 'sku' ) ).map( r => r.sku ), [ 'DEMO-10', 'DEMO-3000', 'DEMO-5000', 'DEMO-6100' ] ) );
t( 'sort by name', () => assert.strictEqual( rows.slice().sort( ( a, b ) => wcc.compare( a, b, 'name' ) )[0].name, 'Black' ) );

console.log( `All ${n} JS tests passed` );
