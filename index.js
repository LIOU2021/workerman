var $ws = '';
var $myUid = '';
var $myConnectId = '';
var $inputUid = '';
var $allPeople = {};

function addAllPeople(uid, connectionId) {
    if (!$allPeople.hasOwnProperty(uid)) {
        let randomColor = "#" + Math.floor(Math.random() * 16777215).toString(16);
        $allPeople[uid] = {
            connectionId: connectionId,
            color: randomColor
        };

        addUserList(uid, connectionId);
    };
}

function userListEvent(_this) {
    // $().addClass("");
    // .d-none
    // .d-block
    // console.log($(this).data('connect-id'));
    console.log($(_this).data('connect-id'));
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

function deleteAllPeople(uid) {
    delete $allPeople[uid];
}

function proccessWsMessage(msg) {
    let data = JSON.parse(msg);
    if (data.status == 200) {
        let onlineHtml = '';
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

                $(".msg_card_body").append(onlineHtml);

                break;
            case 'onConnect':

                break;
            case 'onGroup':

                let from_uid = data.data.from_uid;
                let from_connectionId = data.data.from_connectionId;
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
                $(".card-body.msg_card_body").append(onlineHtml);
                break;
            // case 'infor':
            //     break;
            case 'onClose':
                let uid = data.data.uid;

                deleteAllPeople(uid);

                onlineHtml = `
                <div class="d-flex justify-content-center ">
                    <div class="msg_online_cotainer">
                        ${uid} 下線 - ${data.datetime}
                    </div>
                </div>`;

                $(".msg_card_body").append(onlineHtml);
                break;
            // case 'onMessage':
            //     break;
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