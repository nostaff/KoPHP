<?php
/**
 * Creates the methodTable for a service class.
 *
 * @usage $this->methodTable = MethodTable::create($this);
 * @author Christophe Herreman
 * @since 05/01/2005
 * @version $id$
 * 
 * Special contributions by Allessandro Crugnola and Ted Milker
 */

if (!defined('T_ML_COMMENT')) {
   define('T_ML_COMMENT', T_COMMENT);
} else {
   define('T_DOC_COMMENT', T_ML_COMMENT);
}

function strrstr($haystack, $needle)
{
	return substr($haystack, 0, strpos($haystack.$needle,$needle));
}

function strstrafter($haystack, $needle)
{
	return substr(strstr($haystack, $needle), strlen($needle));
}

class MethodTable
{

	/**
	 * Creates the methodTable for a passed class.
	 *
	 * @static
	 * @access public
	 * @param $className(String) The name of the service class.
	 *        May also simply be __FILE__
	 * @param $servicePath(String) The location of the classes (optional)
	 */
	public static function create($className, $servicePath = NULL, &$classComment){
		$methodTable = array();
		if(file_exists($className))
		{
			//The new __FILE__ way of doing things was used
			$sourcePath = $className;
			$className = str_replace("\\", '/', $className);
			$className = substr($className, strrpos($className, '/') + 1);
			$className = str_replace('.php', '', $className);
		}
		else
		{
			$className = str_replace('.php', '', $className);
			$fullPath = str_replace('.', '/', $className);
			$className = $fullPath;
			if(strpos($fullPath, '/') !== FALSE)
			{
				$className = substr(strrchr($fullPath, '/'), 1);
			}
			
			if($servicePath == NULL)
			{
				$servicePath = "../services/";
			}
			$sourcePath = $servicePath . $fullPath . ".php";
		}
		if(!file_exists($sourcePath))
		{
			trigger_error("The MethodTable class could not find {" . 
				$sourcePath . "}", 
				E_USER_ERROR);
		}
		
		//PHP5
		$classMethods = MethodTable::getClassMethodsReflection($sourcePath, $className, $classComment);
		
		foreach ($classMethods as $key => $value) {
			$methodSignature = $value['args'];
			$methodName = $value['name'];
			$methodComment = $value['comment'];
			
			$description = MethodTable::getMethodDescription($methodComment) . " " . MethodTable::getMethodCommentAttribute($methodComment, "desc");
			$description = trim($description);
			$access = MethodTable::getMethodCommentAttributeFirstWord($methodComment, "access");
			$roles = MethodTable::getMethodCommentAttributeFirstWord($methodComment, "roles");
			$instance = MethodTable::getMethodCommentAttributeFirstWord($methodComment, "instance");
			$returns = MethodTable::getMethodCommentAttributeFirstLine($methodComment, "returns");
			$pagesize = MethodTable::getMethodCommentAttributeFirstWord($methodComment, "pagesize");
			$params = MethodTable::getMethodCommentArguments($methodComment);
						
			//description, arguments, access, [roles, [instance, [returns, [pagesize]]]]
			$methodTable[$methodName] = array();
			//$methodTable[$methodName]["signature"] = $methodSignature; //debug purposes
			$methodTable[$methodName]["description"] = ($description == "") ? "No description given." : $description;
			$methodTable[$methodName]["arguments"] = MethodTable::getMethodArguments($methodSignature, $params);
			$methodTable[$methodName]["access"] = ($access == "") ? "private" : $access;
			
			if($roles != "") $methodTable[$methodName]["roles"] = $roles;
			if($instance != "") $methodTable[$methodName]["instance"] = $instance;
			if($returns != "") $methodTable[$methodName]["returns"] = $returns;
			if($pagesize != "") $methodTable[$methodName]["pagesize"] = $pagesize;
		}
		
		$classComment = trim(str_replace("\r\n", "\n", MethodTable::getMethodDescription($classComment)));
		
		return $methodTable;
	}
	
	public static function getClassMethodsReflection($sourcePath, $className, & $classComment)
	{
		$included = require_once ($sourcePath);
		if($included === FALSE) {
			return array();
		}
		
		//Verify that the class exists
		if(!class_exists($className)) {
			return array();
		}
		
		$methodTable = array();
		$class = new ReflectionClass($className);
		$classComment = $class->getDocComment();
		$methods = $class->getMethods();
		
		foreach($methods as $reflectionMethod){
			if($reflectionMethod->isPublic() && $reflectionMethod->name[0] != '_' && $reflectionMethod->name != 'beforeFilter') {
				if($reflectionMethod->isConstructor()) {
					$classComment .= $reflectionMethod->getDocComment();
				} else {
					$reflectionParameter = $reflectionMethod->getParameters();
					
					$methodTableEntry = array();			
					$parameters = array();
					
					foreach($reflectionParameter as $parameter){
						$parameters[] = $parameter->getName();
					}
					
					$methodTableEntry['name'] = $reflectionMethod->name;
					$methodTableEntry['args'] = '(' . implode(', ', $parameters);
                    $methodTableEntry['comment'] = $reflectionMethod->getDocComment();
					
					$methodTable[] = $methodTableEntry;
				}
			}
		}
		
		return $methodTable;
	}
	
	/**
	 * 
	 */
	public static function getMethodCommentArguments($comment)
	{
		$pieces = explode('@param', $comment);
		$args = array();
		if(is_array($pieces) && count($pieces) > 1)
		{
			for($i = 0; $i < count($pieces) - 1; $i++)
			{
				$ps = strrstr($pieces[$i + 1], '@');
				$ps = strrstr($ps, '*/');
				$args[] = MethodTable::cleanComment($ps);
			}
		}
		return $args;
	}
	
	
	/**
	 * Returns the description from the comment.
	 * The description is(are) the first line(s) in the comment.
	 *
	 * @static
	 * @private
	 * @param $comment(String) The method's comment.
	 */
	public static function getMethodDescription($comment){
		$comment = MethodTable::cleanComment(strrstr($comment, "@"));
		return trim($comment);
	}
	
	
	/**
	 * Returns the value of a comment attribute.
	 *
	 * @static
	 * @private
	 * @param $comment(String) The method's comment.
	 * @param $attribute(String) The name of the attribute to get its value from.
	 */
	public static function getMethodCommentAttribute($comment, $attribute){
		$pieces = strstrafter($comment, '@' . $attribute);
		if($pieces !== FALSE)
		{
			$pieces = strrstr($pieces, '@');
			$pieces = strrstr($pieces, '*/');
			return MethodTable::cleanComment($pieces);
		}
		return "";
	}
	
	/**
	 * Returns the value of a comment attribute.
	 *
	 * @static
	 * @private
	 * @param $comment(String) The method's comment.
	 * @param $attribute(String) The name of the attribute to get its value from.
	 */
	public static function getMethodCommentAttributeFirstLine($comment, $attribute){
		$pieces = strstrafter($comment, '@' . $attribute);
		if($pieces !== FALSE)
		{
			$pieces = strrstr($pieces, '@');
			$pieces = strrstr($pieces, "*");
			$pieces = strrstr($pieces, "/");
			$pieces = strrstr($pieces, "-");
			$pieces = strrstr($pieces, "\n");
			$pieces = strrstr($pieces, "\r");
			$pieces = strrstr($pieces, '*/');
			return MethodTable::cleanComment($pieces);
		}
		return "";
	}
	
	public static function getMethodCommentAttributeFirstWord($comment, $attribute){
		$pieces = strstrafter($comment, '@' . $attribute);
		if($pieces !== FALSE)
		{
			$val = MethodTable::cleanComment($pieces);
			return trim(strrstr($val, ' '));
		}
		return "";
	}
	
	/**
	 * Returns an array with the arguments of a method.
	 *
	 * @static
	 * @access private
	 * @param $methodSignature (String)The method's signatureg;
	 */
	public static function getMethodArguments($methodSignature, $commentParams){
		if(strlen($methodSignature) < 2){
			//no arguments, return an empty array
			$result = array();
		}else{
			//clean the arguments before returning them
			$result = MethodTable::cleanArguments(explode(",", $methodSignature), $commentParams);
		}
		
		return $result;
	}
	
	/**
	 * Cleans the arguments array.
	 * This method removes all whitespaces and the leading "$" sign from each argument
	 * in the array.
	 *
	 * @static
	 * @access private
	 * @param $args(Array) The "dirty" array with arguments.
	 */
	public static function cleanArguments($args, $commentParams){
		$result = array();
		
		foreach($args as $index => $arg){
			$arg = strrstr(str_replace('(', '', $arg), '=');
			if(!isset($commentParams[$index]))
			{
				$result[] = trim($arg);
			}
			else
			{
				$start = trim($arg);
				$end = trim(str_replace('$', '', $commentParams[$index]));
				//echo($start);
				//echo($end);
				if($end != "" && $start != "" && strpos(strtolower($end), strtolower($start)) === 0)
				{
					$end = substr($end, strlen($start));
				}
				$result[] = $start . ' - ' . trim($end);
			}
		}
		
		return $result;
	}
	
	
	/**
	 * Cleans the comment string by removing all comment start and end characters.
	 *
	 * @static
	 * @private
	 * @param $comment(String) The method's comment.
	 */
	public static function cleanComment($comment){
		$comment = str_replace("/**", "", $comment);
		$comment = str_replace("*/", "", $comment);
		$comment = str_replace("*", "", $comment);
		$comment = str_replace("\r", "", trim($comment));
		$comment = preg_replace("{\n[ \t]+}", "\n", trim($comment));
		$comment = str_replace("\n", "\\n", trim($comment));
		$comment = preg_replace("{[\t ]+}", " ", trim($comment));
		
		$comment = str_replace("\"", "\\\"", $comment);
		return $comment;
	}

}
?>