<?php

namespace Botble\Contact\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Forms\FieldOptions\StatusFieldOption;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\FormAbstract;
use Botble\Contact\Enums\ContactStatusEnum;
use Botble\Contact\Http\Requests\EditContactRequest;
use Botble\Contact\Models\Contact;

class ContactForm extends FormAbstract
{
    public function setup(): void
    {
        Assets::addScriptsDirectly('vendor/core/plugins/contact/js/contact.js')
            ->addStylesDirectly('vendor/core/plugins/contact/css/contact.css');

        /** @var \Botble\Contact\Models\Contact|null $contact */
        $contact = $this->getModel();

        if ($contact && $contact->getKey()) {
            $deleteUrl   = route('contacts.destroy', $contact->getKey());
            $contactName = e($contact->name ?? 'this contact');

            add_filter('base_action_form_actions_extra', function () use ($deleteUrl, $contactName) {
                return '
<script>
document.addEventListener("DOMContentLoaded", function () {
    /* Find the btn-list that contains our delete trigger (first sidebar card only) */
    var delBtn = document.getElementById("contactDeleteTrigger");
    if (!delBtn) return;
    var btnList = delBtn.closest(".btn-list");
    if (!btnList) return;
    /* Make the container stack vertically */
    btnList.style.flexDirection = "column";
    btnList.style.gap = "6px";
    /* Make every existing btn full-width and compact */
    btnList.querySelectorAll(".btn").forEach(function (btn) {
        btn.classList.add("w-100");
        btn.style.fontSize = "0.8125rem";
        btn.style.padding = "0.375rem 0.75rem";
        btn.style.justifyContent = "center";
    });
});
<\/script>
<hr class="my-2" style="border-color:#e8ebee;">
<button type="button"
        id="contactDeleteTrigger"
        class="btn btn-danger w-100"
        style="font-size:0.8125rem;padding:0.375rem 0.75rem;justify-content:center;"
        data-bs-toggle="modal" data-bs-target="#contactDeleteModal">
    <svg class="icon icon-left" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/>
        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
    </svg>
    Delete Contact
</button>

<div class="modal fade" id="contactDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#e53e3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/>
                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                        </svg>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1">Delete Contact?</h6>
                <p class="text-muted small mb-4"><strong>' . $contactName . '</strong> will be permanently deleted and cannot be recovered.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="' . $deleteUrl . '" class="d-inline">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-danger px-4">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';
            });
        }

        $this
            ->model(Contact::class)
            ->setValidatorClass(EditContactRequest::class)
            ->add(
                'status',
                SelectField::class,
                StatusFieldOption::make()
                    ->choices(ContactStatusEnum::labels())
            )
            ->setBreakFieldPoint('status')
            ->addMetaBoxes([
                'information' => [
                    'title' => trans('plugins/contact::contact.contact_information'),
                    'content' => view('plugins/contact::contact-info', ['contact' => $this->getModel()])->render(),
                ],
                'replies' => [
                    'title' => trans('plugins/contact::contact.replies'),
                    'content' => view('plugins/contact::reply-box', ['contact' => $this->getModel()])->render(),
                ],
            ]);
    }
}
