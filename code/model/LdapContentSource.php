<?php
/*

Copyright (c) 2009, SilverStripe Australia PTY LTD - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
OF SUCH DAMAGE.
*/

/**
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class LdapContentSource extends ExternalContentSource
{
    public static $db = array (
		'Host' => 'Varchar(64)',
		'Port' => 'Int',
		'BaseDN' => 'Varchar(64)', // should child items of this be seen in menus?
		'BindUser' => 'Varchar(64)',
		'BindPass' => 'Varchar(64)',
	);

	public function getCMSFields()
	{
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new TextField('Host', _t('LdapContentSource.HOST', 'Host')));
		$fields->addFieldToTab('Root.Main', new TextField('Port', _t('LdapContentSource.PORT', 'Port'), 389));
		$fields->addFieldToTab('Root.Main', new TextField('BaseDN', _t('LdapContentSource.BASEDN', 'Base DN')));
		$fields->addFieldToTab('Root.Main', new TextField('BindUser', _t('LdapContentSource.BINDUSER', 'Bind User (if any)')));
		$fields->addFieldToTab('Root.Main', new PasswordField('BindPass', _t('LdapContentSource.BINDPASS', 'Bind Pass (if any)')));

		return $fields;
	}

	/**
	 *
	 * @var Zend_Ldap
	 */
	protected $connection;

	/**
	 * returns the class that actually does the LDAP querying
	 * 
	 * @return Zend_Ldap
	 */
	public function getLdap()
	{
		// get the connection if not set
		if (!$this->connection && $this->Host) {
			$options = array(
				'host'              => $this->Host,
				'username'          => $this->BindUser,
				'password'          => $this->BindPass,
				'bindRequiresDn'    => true,
				// 'accountDomainName' => $this->Host,
				'baseDn'            => $this->BaseDN,
			);
			$this->connection = new Zend_Ldap($options);
			$this->connection->connect();
		}

		return $this->connection;
	}

	public function getObject($id)
	{
		$realId = $this->decodeId($id);
		$conn = $this->getLdap();
		if ($conn) {
			try {
				$item = $conn->getNode($realId);
				if ($item) {
					return new LdapContentItem($this, $item);
				}
			} catch (Zend_Ldap_Exception $zle) {
				SS_Log::log($zle, Zend_Log::ERR);
			}
		}
	}

	public function stageChildren()
	{
		// get the root, create an object, and get ITs children
		$conn = $this->getLdap();
		if ($conn) {
			$item = $this->getObject($this->encodeId($this->BaseDN));
			if ($item) {
				return $item->stageChildren();
			}
		}
		return new DataObjectSet();
	}
}
?>