<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

final class DifferentialReviewersFieldSpecification
  extends DifferentialFieldSpecification {

  private $reviewers;
  private $error;

  public function shouldAppearOnRevisionView() {
    return true;
  }

  public function getRequiredHandlePHIDsForRevisionView() {
    return $this->getReviewerPHIDs();
  }

  public function renderLabelForRevisionView() {
    return 'Reviewers:';
  }

  public function renderValueForRevisionView() {
    $reviewer_phids = $this->getReviewerPHIDs();
    if (!$reviewer_phids) {
      return '<em>None</em>';
    }

    $links = array();
    foreach ($reviewer_phids as $reviewer_phid) {
      $links[] = $this->getHandle($reviewer_phid)->renderLink();
    }

    return implode(', ', $links);
  }

  private function getReviewerPHIDs() {
    $revision = $this->getRevision();
    return $revision->getReviewers();
  }

  public function shouldAppearOnEdit() {
    return true;
  }

  protected function didSetRevision() {
    $this->reviewers = $this->getReviewerPHIDs();
  }

  public function getRequiredHandlePHIDsForRevisionEdit() {
    return $this->reviewers;
  }

  public function setValueFromRequest(AphrontRequest $request) {
    $this->reviewers = $request->getArr('reviewers');
    return $this;
  }

  public function validateField() {
    if (in_array($this->getUser()->getPHID(), $this->reviewers)) {
      $this->error = 'Invalid';
      throw new DifferentialFieldValidationException(
        "You may not review your own revision!");
    }
  }

  public function renderEditControl() {
    $reviewer_map = array();
    foreach ($this->reviewers as $phid) {
      $reviewer_map[$phid] = $this->getHandle($phid)->getFullName();
    }
    return id(new AphrontFormTokenizerControl())
      ->setLabel('Reviewers')
      ->setName('reviewers')
      ->setDatasource('/typeahead/common/users/')
      ->setValue($reviewer_map)
      ->setError($this->error);
  }

  public function willWriteRevision(DifferentialRevisionEditor $editor) {
    $editor->setReviewers($this->reviewers);
  }

  public function shouldAppearOnCommitMessage() {
    return true;
  }

  public function getCommitMessageKey() {
    return 'reviewerPHIDs';
  }

  public function setValueFromParsedCommitMessage($value) {
    $this->reviewers = nonempty($value, array());
    return $this;
  }

  public function renderLabelForCommitMessage() {
    return 'Reviewers';
  }

  public function getRequiredHandlePHIDsForCommitMessage() {
    return $this->reviewers;
  }

  public function renderValueForCommitMessage($is_edit) {
    if (!$this->reviewers) {
      return null;
    }

    $names = array();
    foreach ($this->reviewers as $phid) {
      $names[] = $this->getHandle($phid)->getName();
    }

    return implode(', ', $names);
  }

}
