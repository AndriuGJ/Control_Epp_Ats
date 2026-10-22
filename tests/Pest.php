<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function firmaAtsPrueba(): string
{
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAZAAAAB4CAYAAADc36SXAAAFKklEQVR4nO3dS3bbOBBAUTunF+Ch95D9LyV76GHvQD3ozrGPQopkEZ8CcO80P8Wi+FiAKL0/Ho83ALjqR+8HAMCYBASAEAEBIERAAAgREABCBASAEAEBIERAAAgREABCBASAEAEBIERAAAgREABCBASAEAEBIERAAAgREABCBASAEAEBIERAAAgREABCBASAEAEBIERAEvn4/Pno/RgAznp/PJyzMniOxz9//3rv9VgAzjCBJGDyAEYkIJ1txcP0AYxAQDoSD2BkAtKJZSuy+fj8+XBccoVN9A72XqSmD3pwPBL1V+8HAPTzauL4/mtiwhYTSGOu9sjg6Dh0nHKGgDT06mrPC5NWrsbBmz3YIyCNiAe93Z0qTCU8E5AGjt7Z4gVITaUvXoSE3wSkMvGgp9one8tbaxOQisSDXlpPCaaSNQlIJXtXZt4aSU2999qEZC0CUoF40EO2k7flrfm5kbAwLxpayxaO53//++NzETUXE0hBr+LhhUMNWeOxZaTHyjkmkEJMHrQ04sn4+2PbmkoyP3a2mUAKOIqH6YNSem+SlzZiCPliAqnAwU8NM55s9z57y1QyBhPITUffZW764K4Zw/GK5eBxmEBu8OU71LZaPN7evHtrJCaQoDNXSQ56olYMxx4/i7wEJEA8qGW2TfLSLG/lYgnrIgcwtbjSPmZ5KxcTyAVn4+GA5grhiPOz68sEcpLJg9IsV93n5sS+TCAnXImH6YMzXDnX42fbjgnkgMmDkpzc6nNzYjsmkBeuxsP00Vf2E4R49ONCsA4TyA4H3BhGuJlTOPrz7q06TCAbIvFwMNZ3JRYZngOb5HmJehkmkBMcVH1Ep4sMz5cTVG7evVWGCeTJ0YcjHv0ZB17M2Vgc3Xez93taEY5xee6uE5BvLF21cycYr/4e8eAuz+N5lrD+Z9O8rjPBuPrzzhIPJ5y57C1v8ScBeYvHw/SxrdR0kZ1N8vnt3VPCf5YPiMnjvh7B6D19mDrW4nndtnRA7sRj1ekjw3TR82pQOODLsgExeZyTIRgZ/m3LVfCnJQNyNx4zTx81NrtL6rF0ZeqAbcsFxOTxZYTpoifhgNeWCkiJeIw8fYwejJbTh3jAsWUCstrkMXosnrWKh3DAeUsEpFQ8Mk8fswWjNZvkcN0SAXk2Qzyyb3aXVHv6MHVAzPQB6X3DWQkrTxc17/kQDrhn6oCUOvm0nj5WDsaRUv9n8YD7pg3IKJvmYrGvxvQoHFDOlAEpGY/S04dg9GGTHMqbLiDZJo+VNrtLKjl9mDqgjqkCUjoeV6cP00UZpeIhHFDXNAHpMXkIRk6Wq6CNKQJSIx7P04dYtHF3+jB1QDvDB6TF5OGKto07b7sWDmhv6IDUioeTUQ6RL/eK/Hkg5v3xGPOrfmtPHh+fPx9OQO1Elq6EA/oacgJpsWzlJJSXJUXIYciAPHPSGNuV6cPUAXkMF5AZPhyRL2efT+GAfH70fgBX1PxkVvISD8hpmAkk20eUcN/R9CEckNsQARGP+byaJm2SwxjSB0Q81vD7OTV1wDhSB0Q85rS1dCUcMJ7Um+jPJw8nkzmJB4xpiDvR3RU+D9+PAvMYIiDM4SgewgFjSb2ExTrEA8aTehOdedjngPmYQGhiKxTiAWOzB0JT3hAB8xAQAEIsYQEQIiAAhAgIACECAkCIgAAQIiAAhAgIACECAkCIgAAQIiAAhAgIACECAkCIgAAQIiAAhAgIACECAkCIgAAQIiAAhAgIACECAkCIgAAQIiAAhPwLnagYvV7kWxsAAAAASUVORK5CYII=';
}
