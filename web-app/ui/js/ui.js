
/**
 * Function to send request to the back-end to create a PC user
 *    back-end will create a user and will return PC user's QR-code and user-id
 */
function create_pc_user_with_qr() {
    var data = new FormData();
	$.ajax(
		{
			type: "POST",
			url: '../backend/create_pc_user.php',	// back-end url to call
			async: true,
			cache: false,
			dataType: 'json',
			data: data,
			processData: false,  // tell jQuery not to process the data
			contentType: false,  // tell jQuery not to set contentType
			success: function (data, textStatus) {
				console.log("create_pc_user: success");
				
                // show user's QR-code and user-id on the web-page
                $("#pc_user_id").html(data.user_id);
				$("#pc_user_qr").attr("src", "data:image/gif;base64," + data.user_qr);
				$("#created_user").attr("style", "display: block;")
			},
			
			error: function (data, textStatus, errorThrown) {
				console.log("create_pc_user error: " + data + "\n" + textStatus + "\n" + errorThrown);
			}
		}
	);
}