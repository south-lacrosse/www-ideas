/**
 * NOTE: This code works on the old REST format, which isnt available now. Code kept
 * in case we want to do something similar, in which case this can be used as a base.
 * 
 * Code used in Google Sheet "SEMLA Referee Assignments" to import fixtures. User
 * can select a date and it will import from REST XML the fictures, captains, links
 * to the home team.
 * 
 * Ensure this is also stored in the Git repository, as I don't know how
*  Google keep track of these things, and I don't want to lose it!
 */
function onOpen() {
  SpreadsheetApp.getActive().addMenu('Fixtures', [
    {name: 'Import fixtures...', functionName: 'createImportFixturesDialog'},
    {name: 'Remove empty rows/columns on current sheet', functionName: 'removeCurrent'},
    {name: 'Remove empty rows/columns on all sheets', functionName: 'removeAll'}
  ]);
}

function createImportFixturesDialog() {
  var app = UiApp.createApplication();

  var panel = app.createVerticalPanel();
  var picker = app.createDatePicker().setName('picker');
  // default to next Saturday
  var defaultDate = new Date();
  defaultDate.setHours(12,0,0,0); // cater for daylight savings
  var day = defaultDate.getDay();
  if (day != 6) {
    defaultDate.setDate(defaultDate.getDate() + 6 - day);
  }
  picker.setValue(defaultDate);
  
  var buttonPanel = app.createHorizontalPanel()
    .add(app.createButton('OK').addClickHandler(app.createServerClickHandler('importDate').addCallbackElement(picker)))
    .add(app.createButton('Close').addClickHandler(app.createServerClickHandler('close')));
  
  panel.add(app.createLabel('Import fixtures for date:'))
    .add(picker)
    .add(buttonPanel);

  app.add(panel);
  SpreadsheetApp.getActiveSpreadsheet().show(app);
}

// Close everything when the close button is clicked
function close() {
  var app = UiApp.getActiveApplication();
  app.close();
  // The following line is REQUIRED for the widget to actually close.
  return app;
}

function importDate(e) {
  var datePicked = e.parameter.picker;
  datePicked.setHours(12,0,0,0); // cater for daylight savings
  var importDate = Utilities.formatDate(datePicked, 'GMT', 'yyyy-MM-dd');

  var uri = 'http://www.southlacrosse.org.uk/rest/fixtures-by-date/mens/' + importDate + '.xml';
  var response = UrlFetchApp.fetch(uri, { 'muteHttpExceptions' : true });
  var responseCode = response.getResponseCode();
  if (responseCode == 404) {
    Browser.msgBox('There are no fixtures on the SEMLA server for '+datePicked.toDateString());
    return UiApp.getActiveApplication();
  } else if  (responseCode != 200) {
    Browser.msgBox('Error accessing the SEMLA server. Error code '+responseCode);
    return UiApp.getActiveApplication();
  }

  var document = XmlService.parse(response.getContentText());
  
  var fixtures = document.getRootElement().getChildren('fixture');
  var rows = [];
  for (var i = 0; i < fixtures.length; i++) {
    var fixture = fixtures[i];
    
    var vCol = '';
    var homeTeam = getChild(fixture,'home-team');
    if (homeTeam != '') {
      vCol = 'v';
      var clubPage = fixture.getChild('club-page');
      if (clubPage != null) {
        homeTeam = '=HYPERLINK("http://www.southlacrosse.org.uk/clubs/'+clubPage.getText()+'","'+homeTeam+'")';
      }
    }
    
    var time = getChild(fixture,'time');
    if (time != '') {
      time = "'"+time; // make into text field so it displays asis
    }
    
    var contactNode = fixture.getChild('contact');
    var contact;
    if (contactNode == null) {
      contact = ['','',''];
    } else {
      var email = getChild(contactNode,'email');
      if (email != '') {
        email = '=HYPERLINK("mailto:'+email+'","'+email+'")';
      }
      contact = [getChild(contactNode,'name'),email,getChild(contactNode,'tel')];
    }
    
    rows.push([homeTeam,vCol,getChild(fixture,'away-team'),time,getChild(fixture,'competition'),
              contact[0],contact[1],contact[2]]);
  }

  var sheetName = importDate.substring(8,10)+importDate.substring(4,8)+importDate.substring(0,4);
  
  var ss = SpreadsheetApp.getActive();
  var sheet = ss.insertSheet(sheetName, 0);
  var headings = ['Panel Referee','Referee 1','Home Referee','Home','','Away','Time','Comp'
                                        ,'Home Team Contact','Contact Email','Contact Tel'];
  sheet.getRange(3, 1, 1, headings.length).setValues([headings]).setFontWeight('bold');
  sheet.getRange(4, 4, fixtures.length, headings.length - 3).setValues(rows);
  for (var i = headings.length; i > 0; i--) {
    sheet.autoResizeColumn(i);
  }
  sheet.getRange('A1').setValue('Refereeing Assignments for '+datePicked.toDateString())
      .setFontWeight('bold').setFontSize(14).setWrap(false);
  sheet.getRange('A1:E1').merge();
  sheet.setRowHeight(1, 21);
  
  removeEmptyRowsColumns(sheet);

  return close();
}

function getChild(node, name) {
  var child = node.getChild(name);
  return child ? child.getText() : '';
}

function removeCurrent() {
  removeEmptyRowsColumns(SpreadsheetApp.getActiveSheet());
}

function removeAll() {
  var ss = SpreadsheetApp.getActive();
  var allsheets = ss.getSheets();
  for (var s in allsheets) {
    removeEmptyRowsColumns(allsheets[s]);
  }
}

function removeEmptyRowsColumns(sheet) {
  var lastRow = sheet.getLastRow();
  var maxRow = sheet.getMaxRows();
  if (lastRow < maxRow) {
    sheet.deleteRows(lastRow+1, maxRow-lastRow);
  }
  var lastCol = sheet.getLastColumn();
  var maxCol = sheet.getMaxColumns();
  if (lastCol < maxCol) {
    sheet.deleteColumns(lastCol+1, maxCol-lastCol);
  }
}