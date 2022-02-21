<?php

namespace Tests\Models;

use Grosv\EloquentSheets\SheetModel;

class DefineHeadersModel extends SheetModel
{
    protected $spreadsheetId = '1dEQg2Z1kgqUy3db3FOjeLAVVMGEX7MF-_rFtQQ6UxG8';
    protected $sheetId = '282825363';
    protected $headerRow = '1';
    protected $headers = ['id', 'name', 'email'];
}
