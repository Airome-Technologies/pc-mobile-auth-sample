/**
 * Function to send request to the back-end to create a PC user
 *    back-end will create a user and will return PC user's QR-code and user-id
 */
function create_pc_user_with_qr() {
    var data = new FormData();
    $.ajax({
        type: "POST",
        url: '../backend/pers/qr/create_pc_user_qr.php', // back-end url to call
        async: true,
        cache: false,
        dataType: 'json',
        data: data,
        processData: false, // tell jQuery not to process the data
        contentType: false, // tell jQuery not to set contentType
        success: function(data, textStatus) {
            console.log("create_pc_user: success");

            // show user's QR-code and user-id on the web-page
            $("#pc_user_id").html(data.user_id);
            $("#pc_user_qr").attr("src", "data:image/gif;base64," + data.user_qr);
            $("#created_user_qr").attr("style", "display: block;")
        },

        error: function(data, textStatus, errorThrown) {
            console.log("create_pc_user error: " + data + "\n" + textStatus + "\n" + errorThrown);
        }
    });
}

/**
 * Function to send request to the back-end to create an Alias
 *    back-end will create an Alias and will return it with Activation Code
 * 
 * !!! WARNING - we create Activation Code here for DEMO PURPOSES ONLY
 *     You should create Activation Code at the moment when you requests key JSON from PC Server
 *     After you have created Activation Code you should send it to a user with another channel
 *     It can be email, SMS, push or something else
 * 
 *     We can not send SMS or something here in demo, that's why we create activation code here
 */
function create_alias() {
    var data = new FormData();
    $.ajax({
        type: "POST",
        url: '../backend/pers/alias/create_alias.php', // back-end url to call
        async: true,
        cache: false,
        dataType: 'json',
        data: data,
        processData: false, // tell jQuery not to process the data
        contentType: false, // tell jQuery not to set contentType
        success: function(data, textStatus) {
            console.log("create_alias: success");

            // show user's QR-code and user-id on the web-page
            $("#alias").html(data.alias);
            $("#activation_code").html(data.activation_code);
            $("#created_user_alias").attr("style", "display: block;")
        },

        error: function(data, textStatus, errorThrown) {
            console.log("create_alias error: " + data + "\n" + textStatus + "\n" + errorThrown);
        }
    });
}
