var baseURL = document.location.origin + "/" + window.location.pathname.split('/')[1] + "/bookingfrontend/";
var urlParams = [];
CreateUrlParams(window.location.search);

var bookableresource = ko.observableArray();
var bookingDates = ko.observableArray();

ko.validation.locale('nb-NO');


function applicationModel()  {
    var self = this;
    self.bookingDate = ko.observable();
    self.bookingStartTime = ko.observable();
    self.bookingEndTime = ko.observable();
    self.repeat = ko.observable(false);
    self.bookableResource = bookableresource;
    self.isResourceSelected = ko.computed(function() {
        
        for(var i=0; i<self.bookableResource().length; i++) {
            if(self.bookableResource()[i].selected()) {
               return true;
           }
       }
       return false;       
    }).extend({ required: true });
    
    self.date = ko.observableArray().extend({
        minLength: 1
    });
    self.addDate = function () {
        
        if ( self.bookingDate() && self.bookingStartTime() && self.bookingEndTime()) {
            var start =  new Date(self.bookingDate());
            start.setHours(new Date(self.bookingStartTime()).getHours());
            start.setMinutes(new Date(self.bookingStartTime()).getMinutes());
            var end =  new Date(self.bookingDate());
            end.setHours(new Date(self.bookingEndTime()).getHours());
            end.setMinutes(new Date(self.bookingEndTime()).getMinutes());
            
            if(start.getTime() < end.getTime()) {
                self.date.push({start: start, end: end, repeat: self.repeat(), formatedPeriode: formatDate(start, end) });
                self.bookingDate(""); self.bookingStartTime(""); self.bookingEndTime(""); self.repeat(false);
            } else {
                $(".applicationSelectedDates").text("Startid må være tidligere enn sluttid");
            }
            
        }
    };
    
    self.removeDate = function() {
        self.date.remove(this);
    };
    
    self.organizer = ko.observable("").extend({ required: true });
    self.arrangementName = ko.observable("").extend({ required: true });
    self.aboutArrangement = ko.observable("").extend({ required: true });
    self.participantMenU12 = ko.observable("").extend({ required: true, number: true });
    self.participantWomenU12 = ko.observable("").extend({ required: true, number: true });
    self.participantMenO13 = ko.observable("").extend({ required: true, number: true });
    self.participantWomenO13 = ko.observable("").extend({ required: true, number: true });
    self.participantMenO20 = ko.observable("").extend({ required: true, number: true });
    self.participantWomenO20 = ko.observable("").extend({ required: true, number: true });
    self.specialRequirements = ko.observable("");
    self.addApplication = function () {
        if(self.errors().length > 0) {
            self.errors.showAllMessages();
        }
        //    var checkboxes = $("input[type='checkbox']");
        //console.log(!checkboxes.is(":checked"));
    
    };
    
    self.GoToConfirmPage = function () {
	    //getJsonURL = phpGWLink('bookingfrontend/', {menuaction:"bookingfrontend.uibooking.resource_schedule", resource_id:resourceIds[i].id, date:paramDate}, true);
        
        var requestUrl = phpGWLink('bookingfrontend/', { menuaction: "bookingfrontend.uiapplication.add", building_id: 10, }, true);
        
            $.post(requestUrl,{ 
                resources: ["test", "test1"],
                accepted_documents: ["test"],
                from_: [new Date()],
                to_: [new Date(new Date().getDate() + 1)],
                contact_email: "test@test.com",
                contact_email2: "test@test.com",
                //agegroups: ["test"],
                customer_identifier_type: "organization_number",
                customer_organization_number: 995838931
            })
            .done(function( data ) {
                console.log(data);
            });

        if(self.errors().length == 0) {
            var requestUrl = baseURL + "?menuaction=bookingfrontend.uiapplication.add";
            $.post(requestUrl, function( data ) {
                console.log(data);
            });
            //window.location = baseURL+"?menuaction=bookingfrontend.uiapplication.confirm&building_id="+urlParams['building_id'];
        } else {
            self.errors.showAllMessages();
        }
    }
}
var am = new applicationModel();
am.errors = ko.validation.group(am);

ko.applyBindings(am);
        
$(document).ready(function ()
{
    getJsonURL = baseURL+"?menuaction=bookingfrontend.uiresource.index_json&filter_building_id="+urlParams['building_id']+"&phpgw_return_as=json";
    console.log(getJsonURL);
    $.getJSON(getJsonURL, function(result){
        for(var i=0; i<result.results.length; i++) {
            if(result.results[i].building_id == urlParams['building_id']) {
                bookableresource.push({id: result.results[i].id, name: result.results[i].name, selected: ko.observable(false)});
            }
        }
    });
    
    showContent();   
    
    /*$(document).on('click', '#goToConfirmPage', function () {
        
        //window.location = baseURL+"?menuaction=bookingfrontend.uiapplication.confirm&building_id="+urlParams['building_id'];
        console.log(am.isValid()); //false
        $("#printData").append(JSON.stringify(ko.toJS(am), null, 2));
        
    });*/
    
    $('.dropdown-menu').on('click', function () {
        $(this).parent().toggleClass('show');
    });
});

function formatDate(date, end) {
  
  var year = date.getFullYear();

  return ("0" + date.getDate()).slice(-2) + '-' + ("0" + (date.getMonth() + 1)).slice(-2) + '-' + year + " " + 
          ("0" + (date.getHours())).slice(-2)  + ":" + ("0" + (date.getMinutes())).slice(-2) + 
          " - " +
         ("0" + (end.getHours())).slice(-2)  + ":" + ("0" + (end.getMinutes())).slice(-2);
}

YUI({ lang: 'nb-no' }).use(
  'aui-datepicker',
  function(Y) {
    new Y.DatePicker(
      {
        trigger: '.datepicker-btn',
        popover: {
          zIndex: 99999
        },
        on: {
          selectionChange: function(event) { 
              new Date(event.newSelection);
              $(".datepicker-btn").val(event.newSelection);
              am.bookingDate(event.newSelection);
          }
        }
      }
    );
  }
);

YUI({ lang: 'nb-no' }).use(
  'aui-timepicker',
  function(Y) {
    new Y.TimePicker(
      {
        trigger: '.bookingStartTime',
        popover: {
          zIndex: 99999
        },
        mask: 'kl. %H:%M',
        on: {
          selectionChange: function(event) { 
              new Date(event.newSelection);
              $(this).val(event.newSelection);
              am.bookingStartTime(event.newSelection);
              //am.bookingDate(event.newSelection);
          }
        }
      }
    );
  }
);

YUI({ lang: 'nb-no' }).use(
  'aui-timepicker',
  function(Y) {
    new Y.TimePicker(
      {
        trigger: '.bookingEndTime',
        popover: {
          zIndex: 99999
        },
        mask: 'kl. %H:%M',
        on: {
          selectionChange: function(event) { 
              new Date(event.newSelection);
              $(this).val(event.newSelection);
              am.bookingEndTime(event.newSelection);
              //am.bookingDate(event.newSelection);
          }
        }
      }
    );
  }
);