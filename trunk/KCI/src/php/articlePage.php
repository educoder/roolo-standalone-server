<?php
include_once 'header.php';
?>
<?php
require_once 'RooloClient.php';
require_once 'dataModels/Article.php';
require_once 'dataModels/Section.php';
require_once 'util/TagUtil.php';

$action = $_REQUEST['action'];
$articleUriToLoad = isset($_REQUEST['articleUri']) ? $_REQUEST['articleUri'] : '' ;

$roolo = new RooloClient();

$sections = Article::getSections();
$scienceSections = Article::getScienceSections();
$uncatSections = Article::getUncatSections();

$articleUri = '';
$articleTitle = '';
$articleDesc  = '';

$articleFormAction = $action == 'create' ? 'createArticle' : 'saveArticle';
$sectionElos = null;

switch($action){
	case 'create':
		break;
	case 'load':
		if ($articleUriToLoad == ''){
			echo "Trying to load, but no eloId found";
			die();
		}
		
		$article = $roolo->retrieveElo($articleUriToLoad);
		$articleUri = $article->get_uri();
		$articleTitle = $article->get_title();
		$articleDesc = $article->get_desc();
		
		$sectionElos = retrieveSectionElos($article, $roolo);
		$sectionTagElos = retrieveSectionTagElos($sectionElos, $roolo);
		$commentElos = retrieveCommentElos($article, $roolo);
		break;
	case 'createArticle':
		$article = new Article();
		$article->set_uri('');
		$article->set_author($_SESSION['username']);
		$article->set_datecreated('');
		$article->set_datelastmodified('');
		$article->set_title($_REQUEST['articleTitle']);
		$article->set_desc($_REQUEST['articleDesc']);
		
		$response = $roolo->addElo($article);
		$savedArticle = new Article($response);
		$articleUri = $savedArticle->get_uri();
		$articleTitle = $savedArticle->get_title();
		$articleDesc  = $savedArticle->get_desc();

		foreach($sections as $sectionCode => $sectionTitle){
			$section = new Section();
			$section->set_uri('');
			$section->set_author($_SESSION['username']);
			$section->set_datecreated('');
			$section->set_datelastmodified('');
			$section->set_title($sectionCode);
			$section->set_ownerType('Article');
			$section->set_ownerUri($articleUri);
			
			$response = $roolo->addElo($section);
			$sectionElos[$sectionCode] = new Section($response);
		}
		
//		$sectionTagElos = array();
		$sectionTagElos = retrieveSectionTagElos($sectionElos, $roolo);
		$commentElos = retrieveCommentElos($article, $roolo);
		
		break;
	case 'saveArticle':
		$article = new Article();
		$article->set_uri($_REQUEST['articleUri']);
		$article->set_author($_SESSION['username']);
		$article->set_datecreated('');
		$article->set_datelastmodified('');
		$article->set_title($_REQUEST['articleTitle']);
		$article->set_desc($_REQUEST['articleDesc']);
		
		$response = $roolo->updateElo($article);
		$savedArticle = new Article($response);
		
		$articleUri = $savedArticle->get_uri();
		$articleTitle = $savedArticle->get_title();
		$articleDesc  = $savedArticle->get_desc();
		
		$sectionElos = retrieveSectionElos($article, $roolo);
		$sectionTagElos = retrieveSectionTagElos($sectionElos, $roolo);
		$commentElos = retrieveCommentElos($article, $roolo);
		break;
	default:
		echo "NO ACTION FOUND";
		die();
		break;
}

//echo sizeof($sectionElos);
//foreach($sectionElos as $seciontCode => $sectionElo){
//	echo $sectionElo->get_uri()."<br/>";
//}

?>

<script type="text/javascript">
//	$(document).ready(function() {
//		$('textarea.tinymce').tinymce({
//			// Location of TinyMCE script
//			script_url : '/tinymce/jscripts/tiny_mce/tiny_mce.js',
//
//			// General options
//			theme : "advanced",
//			plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
//
//			// Theme options
//			theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
//			theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
//			theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
//			theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
//			theme_advanced_toolbar_location : "top",
//			theme_advanced_toolbar_align : "left",
//			theme_advanced_statusbar_location : "bottom",
//			theme_advanced_resizing : true,
//
//			// Example content CSS (should be your site CSS)
//			//content_css : "css/content.css",
//
//			// Drop lists for link/image/media/template dialogs
//			template_external_list_url : "lists/template_list.js",
//			external_link_list_url : "lists/link_list.js",
//			external_image_list_url : "lists/image_list.js",
//			media_external_list_url : "lists/media_list.js",
//
//			// Replace values for the template plugin
//			template_replace_values : {
//				username : "Some User",
//				staffid : "991234"
//			}
//		});
//	});
	
	editMode = false;
	articleUri = '<?= $articleUri?>';

	function startSectionEdit(sectionCode){
		if (editMode){
			alert('You may only edit one section at a time. Please finish (or cancel) editing other section.');
		}else{
			editLink = $('#'+sectionCode+'_edit_link');
			editLink.hide(400);
			
			contentDiv = $('#'+sectionCode+'_content'); 
			contentDiv.hide(400);

			textarea = $('#'+sectionCode+'_textarea');
			textarea.html(contentDiv.html());
			
			editDiv = $('#'+sectionCode+'_edit_div');
			editDiv.show(400); 
			
			editMode = true;
		}
	}

	function cancelSectionEdit(sectionCode){
		$('#'+sectionCode+'_edit_div').hide(400);
		$('#'+sectionCode+'_content').show(400);
		$('#'+sectionCode+'_edit_link').show(400);
		editMode = false;
	}

	function saveSectionEdit(sectionCode){
		sectionUri = $('#'+sectionCode+'_uri').val();
		sectionContent = $('#'+sectionCode+'_textarea').val();
		$.post('/src/php/ajaxServices/saveSection.php', {'sectionUri': sectionUri, 'sectionCode': sectionCode, 'articleUri': articleUri, 'sectionContent': sectionContent}, 
			function(data){
				contentDiv = $('#'+sectionCode+'_content');
				textarea = $('#'+sectionCode+'_textarea');
				editDiv = $('#'+sectionCode+'_edit_div');
				editLink = $('#'+sectionCode+'_edit_link');

				contentDiv.html(textarea.val());

				editDiv.hide(400);
				contentDiv.show(400);
				editLink.show(400);
				
				editMode = false;
			}
		);
	}

	function addSectionTag(sectionCode, sectionUri){
		tag = $('#'+sectionCode+'_tags_textbox').val();
		$.post('/src/php/ajaxServices/addTag.php', {'tag': tag, 'ownerType': 'Section', 'ownerUri': sectionUri},
			function(data){
				if (data == "DUPLICATE"){
					alert("The tag you entered seems to be a duplicate. Please check and try again!");
				}else{
					$('#'+sectionCode+'_existing_tags').append(data);
					$('#'+sectionCode+'_tags_textbox').val('');
				}
			}
		);
	}

	function removeSectionTag(tagUri, linkElem){
		$.post('/src/php/ajaxServices/removeTag.php', {'tagUri': tagUri}, 
				function(data){
					$(linkElem).parent().hide(400);
				}
		);
		
		return false;
	}

	function articleSaveHandler(){
		if ($('#articleTitle').val() == ''){
			alert('Article title cannot be left empty');
			return false;
		}

		if ($('#articleDesc').val() == ''){
			alert('Article description cannot be left empty');
			return false;
		}

		if (editMode){
			alert('Please finish (or cancel) editing the open section before attempting to save the article.')
			return false;
		}
	}

	function postComment(){
		var commentText = $('#comment_textbox').val();

		$.post('/src/php/ajaxServices/postComment.php', {'commentText': commentText, 'ownerType': 'Article', 'ownerUri': articleUri}, 
				function(data){
					$('#existing_comments').append(data);
					$('#comment_textbox').val('');
				}
		);
	}

</script>
<a href='/src/php/articles.php' /> <?= htmlspecialchars('< Back to Artilces')?></a>
<h2>Article Page</h2>


<form action='articlePage.php' method='GET' onsubmit='return articleSaveHandler();'>
	<h3>Title</h3>
	<input type='text' name='articleTitle' id='articleTitle' value="<?= htmlspecialchars($articleTitle) ?>" size='58'/>
	
	<h3>Description</h3>
	<textarea name='articleDesc' id='articleDesc' rows="10" cols="50" ><?= htmlspecialchars($articleDesc) ?></textarea> <br/>
	<input type='submit' class='SmallButton' value='Save' />
	<input type='hidden' value='<?= $articleFormAction ?>' name='action'/>
	<input type='hidden' value='<?= $articleUri ?>' name='articleUri'/>
</form>

<?php 
if ($action != 'create'){
?>
<h3>Science</h3>
<?php 
	foreach ($scienceSections as $sectionCode){
		$sectionTitle = $sections[$sectionCode];
		$section = $sectionElos[$sectionCode];
		
		echo generateSection($sectionCode, $sectionTitle, $section->get_uri(), $section->getContent());
		echo generateTags($sectionTagElos[$sectionCode], 'Section', $section->get_uri(), $sectionCode); 
	}
	 
	foreach ($uncatSections as $sectionCode){
		$sectionTitle = $sections[$sectionCode];
		$section = $sectionElos[$sectionCode];
		
		echo generateSection($sectionCode, $sectionTitle, $section->get_uri(), $section->getContent());
		echo generateTags($sectionTagElos[$sectionCode], 'Section', $section->get_uri(), $sectionCode);
	}
?>
<h3>References</h3>
<h4>Search for References</h4>
<form>
	<input type='text' name='refsearch_textbox' id='refsearch_textbox' value='' size='20' />
	<input type='button' class='SmallButton' value='Search' onclick='searchReferences()' />
	<div name='refsearch_results_div' id='refsearch_results_div'>
	</div>
</form>
<h3>Comments</h3>
<form>
	<div name='existing_comments' id='existing_comments'>
	<?php
	foreach($commentElos as $commentElo){
		echo $commentElo->generateHtml();
	}
	?>
	</div>
	Post a Comment: <br/>
	<textarea cols='50' rows='10' name='comment_textbox' id='comment_textbox'></textarea> <br/>
	<input type='button' class='SmallButton' value='Post Comment' onclick='postComment()' />
</form>
<?php 
}
?>


<?php
function retrieveCommentElos($article, $roolo){
	$articleUri = $roolo->escapeSearchTerm($article->get_uri());
	$query = "type:Comment AND ownertype:Article AND owneruri:$articleUri";
//	echo $query;
	$results = $roolo->search($query, 'metadata', 'latest');
	
	return $results;
}

function generateSection($sectionCode, $sectionTitle, $sectionUri, $content){
	$o = '';
	$textareaId = $sectionCode.'_textarea';
	$editLinkId = $sectionCode.'_edit_link';
	$contentDivId = $sectionCode.'_content';
	$editDivId = $sectionCode.'_edit_div';
	$formId = $sectionCode.'_form';
	$sectionUriId = $sectionCode.'_uri';
	
	
	$o .= "<form name='$formId' id='$formId'>";
	$o .= "    <span style='font-size:x-large;'>$sectionTitle</span> "; 
	$o .= "    <span name='$editLinkId' id='$editLinkId' style='font-size: small; text-decoration: underline;' onclick=\"startSectionEdit('$sectionCode');\">edit</span>";
	$o .= "    <br/>";
	$o .= "    <div name='$contentDivId' id='$contentDivId' style='width: 80%;'>$content</div>";
	$o .= "    <div name='$editDivId' id='$editDivId' style='display:none;'>";
	$o .= "        <textarea name='$textareaId' id='$textareaId' rows='10' cols='100'></textarea> <br/>";
	$o .= "        <input type='button' class='SmallButton' value='Save' onclick=\"saveSectionEdit('$sectionCode');\" />";
	$o .= "        <span style='font-size: small; text-decoration: underline;' onclick=\"cancelSectionEdit('$sectionCode');\">cancel</span>";
	$o .= "        <input type='hidden' name='$sectionUriId' id='$sectionUriId' value='$sectionUri' />";
	$o .= "    </div>";
	$o .= "    ";
	$o .= "</form>";
	return $o;
}

function retrieveSectionElos($article, $roolo){
	$sectionEloMap = array();
	$articleUri = $article->get_uri(true);
	
	$query = "type:Section AND owneruri:$articleUri";
	
	$sectionElos = $roolo->search($query, 'elo', 'latest');
	foreach($sectionElos as $sectionElo){
//		echo $sectionElo->get_title()."<br/>";
		$sectionEloMap[$sectionElo->get_title()] = $sectionElo;
	}
	
	return $sectionEloMap;
}

function retrieveSectionTagElos($sectionElos, $roolo){
	$sectionTagElos = array();
	foreach($sectionElos as $sectionCode => $section){
		$sectionTagElos[$sectionCode] = retrieveTagsForSection($section->get_uri(true), $roolo);
	}
	return $sectionTagElos;
}

function retrieveTagsForSection($sectionUri, $roolo){
	$query = "type:Tag AND owneruri:$sectionUri AND status:active";
	$sectionTags = $roolo->search($query, 'metadata', 'latest');
	
	return $sectionTags;
}


?>
















<?php 
include_once 'footer.php';
?>