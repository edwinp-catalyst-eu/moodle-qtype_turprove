/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


function movecounter() {}

jQuery(document).ready(function($) {
    // change the function on the finishteeempt button
    var newFinishAttemptFunc = new Function("return confirmclose(event);");
    $("input[name=finishattempt").attr('onclick', '').click(newFinishAttemptFunc);
    
    // setup the shadowbox
    Shadowbox.init({
        overlayOpacity: 0.9
    });
    
    // start the timer element
    if ($("div#timer").length == 1) {
        updatecustomtimer();
    }
});

function updatecustomtimer() {
    if ($("input#time").length == 1) {
        $("span#customtimer").text( $("input#time").val().substr(2, 5) );
        $("td#customtimer").show();
        window.setTimeout(updatecustomtimer, 500);
    }
}

function navigatenext() {
    if (document.URL.search('/attempt.php') > 0) {
        var quizNextPage = $(".thispage").next().attr("href");
        if (null == quizNextPage) {
            $("input[name=finishattempt]").click()
        } else {
            eval($(".thispage").next().attr("href"));
        }
    }
    if (document.URL.search('/review.php') > 0) {
        var navigateurl = $(".next").attr("href")
        if (navigateurl != undefined) {
            window.location = navigateurl;
        }
    }
}

function navigateprev() {
    if (document.URL.search('/attempt.php') > 0) {
        eval($(".thispage").prev().attr("href"));
    }
    if (document.URL.search('/review.php') > 0) {
        var navigateurl = $(".previous").attr("href")
        if (navigateurl != undefined) {
            window.location = navigateurl;
        }
    }
}

document.onkeydown = function(e) {
    e = e || window.event;
    var keyCode = e.keyCode || e.which;

    if(keyCode == 39) {
        navigatenext();
    }
    if(keyCode == 37) {
        navigateprev();
    }
}