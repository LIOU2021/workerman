var $ws = '';
var $myUid = '';
var $myConnectId = '';
var $inputUid = '';
var $allPeople = {};
var $chatRoomId = 0;

function inputKeyupEvent(e) {
    var code = e.keyCode ? e.keyCode : e.which;
    if (code == 13) {  // Enter keycode
        $(`.chatRoom-${$chatRoomId} .send_btn`).click();
    }
}

function senderEvent() {
    let message = '';
    if ($chatRoomId != 0) {
        message = $(`.chatRoom-${$chatRoomId} .type_msg`).val();
    } else {
        message = $(".type_msg").val();
    }

    let msgType = '';
    let msg = "";

    switch ($chatRoomId) {
        case 0:
            msgType = 'all';
            break;
        default:
            msgType = 'user';
            break;
    }

    msg = {
        type: 'message',
        to: msgType,
        to_user: $chatRoomId,
        msg: message,
    };

    $ws.send(JSON.stringify(msg));

    $(".type_msg").val("");
}

function addAllPeople(uid, connectionId) {
    if (!$allPeople.hasOwnProperty(uid)) {
        let randomColor = "#" + Math.floor(Math.random() * 16777215).toString(16);
        $allPeople[uid] = {
            connectionId: connectionId,
            color: randomColor
        };

        addUserList(uid, connectionId);
        addChatRoomList(uid, connectionId);
    };
}

function addChatRoomList(uid, connectionId) {
    if ($myConnectId == connectionId) {
        return false;
    }

    let exists = $(`.chatRoom-${connectionId}`).length;
    if(exists){
        return false;
    }

    let chatRoomHtml = '';
    chatRoomHtml = `<div class="card chatRoom-hide chatRoom-${connectionId}">
    <div class="card-header msg_head">
        <div class="d-flex bd-highlight">
            <div class="user_info">
                <span>${uid}</span>
            </div>

        </div>
        <span id="action_menu_btn"><i class="fas fa-ellipsis-v"></i></span>
        <div class="action_menu">
            <ul>
                <li><i class="fas fa-user-circle"></i> View profile</li>
                <li><i class="fas fa-users"></i> Add to close friends</li>
                <li><i class="fas fa-plus"></i> Add to group</li>
                <li><i class="fas fa-ban"></i> Block</li>
            </ul>
        </div>
    </div>
    <div class="card-body msg_card_body">

    
    </div>
    <div class="card-footer">
        <div class="input-group">
            <textarea name="" class="form-control type_msg"
                placeholder="Type your message..."></textarea>
            <div class="input-group-append">
                <span class="input-group-text send_btn"><i class="fas fa-location-arrow"></i></span>
            </div>
        </div>
    </div>
</div>`;
    $(".chatRoom-body").append(chatRoomHtml);

    $(`.chatRoom-${connectionId} .send_btn`).on('click', function () {
        senderEvent();
    });


    $(`.chatRoom-${connectionId} .form-control.type_msg`).keyup(function (e) {
        inputKeyupEvent(e);
    });

    return true;
}

function deleteChatRoomList(connection_id) {

    $(`.chatRoom-${connection_id}`).remove();
}

function userListEvent(_this) {

    let connectId = $(_this).data('connect-id');
    $chatRoomId = connectId;
    let uid = $(_this).data('uid');


    $('.active').removeClass('active');
    $(_this).parent().addClass('active');

    $(".chatRoom-show").addClass("chatRoom-hide");
    $(".chatRoom-show").removeClass("chatRoom-show");
    $(`.chatRoom-${$chatRoomId}`).removeClass("chatRoom-hide");
    $(`.chatRoom-${$chatRoomId}`).addClass('chatRoom-show');
}

function addUserList(uid, connectionId) {
    if ($myUid == uid) {
        return false;
    }
    let listHtml = '';
    listHtml = `
    <li class="">
        <div class="d-flex bd-highlight user-list" data-connect-id="${connectionId}" data-uid="${uid}">
            <div class="user_info">
                <span>${uid}</span>
                <p>${uid} is online</p>
            </div>
        </div>
    </li>
    `;
    $(".user-list-area").append(listHtml);

    $(".user-list-area .user-list:last").on('click', function () {
        userListEvent(this);
    });

    return true;
}

function deleteUserList(uid) {
    $(`[data-uid='${uid}']`).parent().remove()
}

function deleteAllPeople(uid) {
    delete $allPeople[uid];
}

function proccessWsMessage(msg) {
    let data = JSON.parse(msg);
    if (data.status == 200) {
        let onlineHtml = '';
        let from_uid = '';
        let from_connectionId = '';
        switch (data.data.type) {
            case 'onBind':
                if ($inputUid == data.data.uid) {
                    $myUid = $inputUid;
                    $myConnectId = data.data.connection_id;
                }

                addAllPeople(data.data.uid, data.data.connection_id);

                onlineHtml = `
                <div class="d-flex justify-content-center ">
                    <div class="msg_online_cotainer">
                        ${data.data.uid} 上線 - ${data.datetime}
                    </div>
                </div>`;

                $(".chatRoom-0>.card-body.msg_card_body").append(onlineHtml);

                break;
            case 'onConnect':

                break;
            case 'onGroup':

                from_uid = data.data.from_uid;
                from_connectionId = data.data.from_connectionId;
                addAllPeople(from_uid, from_connectionId);

                if (from_uid == $myUid) {
                    onlineHtml = `
                <div class="d-flex justify-content-end mb-4">
                    <div class="msg_cotainer_send">
                        <span class="msg_name_send">
                            ${from_uid}
                        </span>
                        ${data.data.msg}
                        <span class="msg_time_send">${data.datetime}</span>
                    </div>
                </div>`;
                } else {
                    onlineHtml = `
                    <div class="d-flex justify-content-start mb-4">
                    <div class="msg_cotainer" style="background-color:${$allPeople[from_uid].color}">
                        <span class="msg_name">
                            ${from_uid}
                        </span>
                        ${data.data.msg}
                        <span class="msg_time">${data.datetime}</span>
                    </div>
                </div>
                `;
                }
                $(".chatRoom-0>.card-body.msg_card_body").append(onlineHtml);
                break;
            // case 'infor':
            //     break;
            case 'onClose':
                let uid = data.data.uid;
                let connection_id = data.data.connection_id;
                deleteAllPeople(uid);
                deleteUserList(uid);
                deleteChatRoomList(connection_id);

                onlineHtml = `
                <div class="d-flex justify-content-center ">
                    <div class="msg_online_cotainer">
                        ${uid} 下線 - ${data.datetime}
                    </div>
                </div>`;

                $(".chatRoom-0>.msg_card_body").append(onlineHtml);
                break;
            case 'onMessage':

                let to_connectionId = '';
                let isSender = data.data.sender;
                let to_Uid = '';
                let from_Uid = '';
                if (isSender) {
                    from_connectionId = data.data.from_connectionId;
                    from_Uid = data.data.from_Uid;
                    to_connectionId = data.data.to_connectionId;
                    to_Uid = data.data.to_Uid;
                } else {
                    to_connectionId = data.data.from_connectionId;
                    to_Uid = data.data.from_Uid;
                    from_connectionId = data.data.to_connectionId;
                    from_Uid = data.data.to_Uid;
                    addAllPeople(to_Uid,to_connectionId);
                }



                if (isSender) {
                    onlineHtml = `
                <div class="d-flex justify-content-end mb-4">
                    <div class="msg_cotainer_send">
                        <span class="msg_name_send">
                            ${from_Uid}
                        </span>
                        ${data.data.msg}
                        <span class="msg_time_send">${data.datetime}</span>
                    </div>
                </div>`;
                } else {
                    onlineHtml = `
                    <div class="d-flex justify-content-start mb-4">
                    <div class="msg_cotainer" style="background-color:${$allPeople[to_Uid].color}">
                        <span class="msg_name">
                            ${to_Uid}
                        </span>
                        ${data.data.msg}
                        <span class="msg_time">${data.datetime}</span>
                    </div>
                </div>
                `;
                }

                let roomId = '';

                roomId = to_connectionId;

                $(`.chatRoom-${roomId}>.card-body.msg_card_body`).append(onlineHtml);
                break;
            default:
                console.log('未定義此訊息格式')
                console.log(data)
                break;
        }
    } else {
        alert('fail : ' + msg);
        //uid命名重疊
        if (data.status == 408) {
            $inputUid = '';
            bindUid();
        }
    }
}

function enterUid() {
    let text = "";
    let input = prompt("Please enter your id:", "myUid01");
    if (input == null || input == "") {
        text = "";
    } else {
        $inputUid = input;
        text = input;
    }
    return text;
}

function bindUid() {
    let uid = '';
    while (uid == '') {
        uid = enterUid();
    }

    let msg = {
        type: 'bind',
        uid: uid
    };
    $ws.send(JSON.stringify(msg));
}

function connect() {
    $ws = new WebSocket("ws://localhost:2000");
    $ws.onopen = function (e) {
        console.log("连接成功");

        //bind Uid
        bindUid();

    };
    $ws.onmessage = function (e) {
        console.log("收到服务端的消息：");
        try {
            console.log(JSON.parse(e.data));
        } catch (exception) {
            console.log(e.data);
        }

        proccessWsMessage(e.data);

    };
}