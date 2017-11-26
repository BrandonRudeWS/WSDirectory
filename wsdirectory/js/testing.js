$(document).ready(function(){
  $("#testing").click(function(){
    $("#text").text("changed");

  });

  $("#othertesting").click(function(){
    $.ajax({
      //url:"/wsdirectory/php/Authentication.php",
      // Delete below line and uncomment above line when done testing.
      url:"/wsdirectory/php/testing.php",
      type: "POST",
      data: {
        action: 'checkUserLogin',
        'user': 'brandon',
        'pass': 1234},
      success: function(response){
      console.log(response);
    },
    error: function(response){
      console.log('error occured');
    }

    });
  });

});
